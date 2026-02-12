<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    /* =======================
       VERIFY WEBHOOK
    ======================= */
    public function verify(Request $request)
    {
        if (
            $request->hub_mode === 'subscribe' &&
            $request->hub_verify_token === env('WHATSAPP_VERIFY_TOKEN')
        ) {
            return response($request->hub_challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /* =======================
       HANDLE INCOMING
    ======================= */
    public function handle(Request $request)
    {
        $message = $request->input('entry.0.changes.0.value.messages.0');

        if (!$message || !isset($message['from'])) {
            return response()->json(['ok' => true]);
        }

        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        $text = $message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? '';

        /* ==========================
           🟢 SEND MESSAGE TO CHATWOOT
        ========================== */

        try {

            $baseUrl   = rtrim(env('CHATWOOT_BASE_URL'), '/');
            $accountId = env('CHATWOOT_ACCOUNT_ID');
            $apiToken  = env('CHATWOOT_API_TOKEN');
            $inboxId   = env('CHATWOOT_INBOX_ID');

            $headers = [
                'api_access_token' => $apiToken,
                'Content-Type'     => 'application/json'
            ];

            /*
            1️⃣ SEARCH CONTACT
            */
            $search = Http::withHeaders($headers)
                ->get("$baseUrl/api/v1/accounts/$accountId/contacts/search", [
                    'q' => $phone
                ]);

            $contactId = $search->json()['payload'][0]['id'] ?? null;

            /*
            2️⃣ CREATE CONTACT IF NOT EXISTS
            */
            if (!$contactId) {

                $createContact = Http::withHeaders($headers)
                    ->post("$baseUrl/api/v1/accounts/$accountId/contacts", [
                        'identifier'   => $phone,
                        'name'         => $phone,
                        'phone_number' => $phone
                    ]);

                $contactId = $createContact->json()['payload']['contact']['id'] ?? null;
            }

            if ($contactId) {

                /*
                3️⃣ CREATE CONVERSATION
                */
                $conversation = Http::withHeaders($headers)
                    ->post("$baseUrl/api/v1/accounts/$accountId/conversations", [
                        'source_id'  => $phone,
                        'inbox_id'   => (int)$inboxId,
                        'contact_id' => (int)$contactId,
                        'status'     => 'open'
                    ]);

                $conversationId = $conversation->json()['id'] ?? null;

                if ($conversationId) {

                    /*
                    4️⃣ ADD INCOMING MESSAGE
                    */
                    Http::withHeaders($headers)
                        ->post("$baseUrl/api/v1/accounts/$accountId/conversations/$conversationId/messages", [
                            'content'      => $text ?: 'رسالة واردة',
                            'message_type' => 'incoming'
                        ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Chatwoot Error: ' . $e->getMessage());
        }

        /* ==========================
           🟢 ORIGINAL TICKET LOGIC
        ========================== */

        if (trim($text) !== 'استلم التذكرة') {
            return response()->json(['ok' => true]);
        }

        $booking = Booking::with('showTime')
            ->where('phone', 'like', "%$phone%")
            ->where('status', 'approved')
            ->whereNotNull('qr_code_path')
            ->latest()
            ->first();

        if (!$booking) {
            return response()->json(['ok' => true]);
        }

        $showTimeText = $booking->showTime
            ? $booking->showTime->date->format('d/m/Y') . ' • ' .
              Carbon::parse($booking->showTime->time)->format('h:i A')
            : 'سيتم إبلاغك بالموعد';

        app(\App\Http\Controllers\Admin\BookingController::class)
            ->sendWhatsAppTicket(
                $booking->phone,
                $booking->qr_code_path,
                $booking->reference_code,
                $booking->full_name,
                $showTimeText
            );

        if (!$booking->whatsapp_sent) {
            $booking->update([
                'whatsapp_sent'    => true,
                'whatsapp_sent_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}

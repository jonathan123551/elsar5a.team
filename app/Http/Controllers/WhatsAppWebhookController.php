<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    /* =======================
     |  VERIFY WEBHOOK
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
     |  HANDLE INCOMING
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

            $baseUrl   = env('CHATWOOT_BASE_URL');
            $accountId = env('CHATWOOT_ACCOUNT_ID');
            $apiToken  = env('CHATWOOT_API_TOKEN');

            // 1️⃣ Create or get contact
            $contactResponse = Http::withHeaders([
                'api_access_token' => $apiToken,
                'Content-Type' => 'application/json'
            ])->post("$baseUrl/api/v1/accounts/$accountId/contacts", [
                'identifier' => $phone,
                'name'       => $phone,
                'phone_number' => $phone
            ]);

            $contactId = $contactResponse->json()['payload']['contact']['id'] ?? null;

            if ($contactId) {

                // 2️⃣ Create conversation
                $conversation = Http::withHeaders([
                    'api_access_token' => $apiToken,
                    'Content-Type' => 'application/json'
                ])->post("$baseUrl/api/v1/accounts/$accountId/conversations", [
                    'contact_id' => $contactId,
                    'inbox_id'   => 1
                ]);

                $conversationId = $conversation->json()['id'] ?? null;

                if ($conversationId) {
                    // 3️⃣ Add message to conversation
                    Http::withHeaders([
                        'api_access_token' => $apiToken,
                        'Content-Type' => 'application/json'
                    ])->post("$baseUrl/api/v1/accounts/$accountId/conversations/$conversationId/messages", [
                        'content' => $text ?: 'رسالة واردة',
                        'message_type' => 'incoming'
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Chatwoot Error: ' . $e->getMessage());
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

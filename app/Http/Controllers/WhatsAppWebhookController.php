<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
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
            $this->forwardToChatwoot($request);
            return response()->json(['ok' => true]);
        }

        // 📱 Normalize phone
        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        // 📝 Get message text
        $text = $message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? '';

        Log::info('INCOMING MESSAGE', [
            'phone' => $phone,
            'text'  => $text
        ]);

        /* ==========================
           🎟 SEND TICKET LOGIC
        ========================== */

        if (trim($text) === 'أستلام التذكرة') {

            // ✅ نجيب أول تذكرة لنفس الرقم ولسه متبعتتش
            $ticket = Ticket::where('phone', $phone)
                ->whereNotNull('qr_image_path')
                ->where('whatsapp_sent', false)
                ->orderBy('id') // مهم عشان الترتيب
                ->first();

            if (!$ticket) {
                Log::info('NO TICKET FOUND', ['phone' => $phone]);
                return response()->json(['status' => 'no ticket']);
            }

            Log::info('SENDING TICKET', [
                'ticket_id' => $ticket->id,
                'code' => $ticket->ticket_code
            ]);

            try {

                // 🎭 نجيب ميعاد الحفلة
                $showTimeText = '';

                if ($ticket->booking && $ticket->booking->showTime) {
                    $showTime = $ticket->booking->showTime;

                    $showTimeText =
                        $showTime->date->format('d/m/Y') . ' • ' .
                        Carbon::parse($showTime->time)->format('h:i A');
                }

                // 📤 إرسال التذكرة
                app(\App\Http\Controllers\Admin\BookingController::class)
                    ->sendWhatsAppTicket(
                        $ticket->phone,
                        $ticket->qr_image_path,
                        $ticket->ticket_code,
                        $ticket->name,
                        $showTimeText
                    );

                // ✅ تحديث الحالة (دي أهم نقطة)
                $ticket->update([
                    'whatsapp_sent' => true
                ]);

                Log::info('TICKET SENT SUCCESS', [
                    'ticket_id' => $ticket->id
                ]);

            } catch (\Exception $e) {

                Log::error('SEND FAILED', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        /* ==========================
           🔁 FORWARD TO CHATWOOT
        ========================== */

        $this->forwardToChatwoot($request);

        return response()->json(['ok' => true]);
    }

    private function forwardToChatwoot(Request $request)
    {
        try {

            $chatwootWebhookUrl = env('CHATWOOT_WHATSAPP_WEBHOOK_URL');

            if (!$chatwootWebhookUrl) {
                Log::error('Chatwoot webhook URL not set');
                return;
            }

            Http::timeout(10)->post($chatwootWebhookUrl, $request->all());

        } catch (\Exception $e) {
            Log::error('Forward to Chatwoot failed: ' . $e->getMessage());
        }
    }
}
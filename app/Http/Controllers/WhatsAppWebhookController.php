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

            // Forward even if no message (for delivery events etc)
            $this->forwardToChatwoot($request);

            return response()->json(['ok' => true]);
        }

        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        $text = $message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? '';

        /* ==========================
           🎟 ORIGINAL TICKET LOGIC
        ========================== */

        if (trim($text) === 'أستلام التذكرة') {

            $booking = Booking::with('showTime')
            ->where('phone', 'like', "%$phone%")
            ->where('status', 'approved')
            ->whereNotNull('qr_code_path')
            ->where('whatsapp_sent', false) // 👈 المهم
            ->oldest() // 👈 مش latest
            ->first();

            if ($booking) {

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

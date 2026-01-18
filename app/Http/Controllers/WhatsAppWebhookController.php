<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Admin\BookingController;

class WhatsAppWebhookController extends Controller
{
    /**
     * ✅ META VERIFICATION (GET)
     */
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');

        if (
            $request->query('hub_mode') === 'subscribe' &&
            $request->query('hub_verify_token') === $verifyToken
        ) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Invalid verification token', 403);
    }

    /**
     * ✅ HANDLE INCOMING MESSAGES (POST)
     */
    public function handle(Request $request)
    {
        $entry = $request->input('entry.0.changes.0.value');

        // مفيش رسالة
        if (!isset($entry['messages'][0])) {
            return response()->json(['ok' => true]);
        }

        $message = $entry['messages'][0];
        $phone   = $message['from'];

        // المستخدم ضغط زر
        if ($message['type'] === 'button') {

            $payload = $message['button']['payload'] ?? null;

            // زر "استلام التذكرة"
            if ($payload === 'GET_TICKET') {

                $booking = Booking::where('phone', $phone)
                    ->where('status', 'approved')
                    ->latest()
                    ->first();

                if (!$booking || !$booking->qr_code_path) {
                    return response()->json(['ok' => true]);
                }

                // إرسال التذكرة (QR)
                app(BookingController::class)->sendWhatsAppTicket(
                    $booking->phone,
                    $booking->qr_code_path,
                    $booking->reference_code,
                    $booking->full_name
                );
            }
        }

        return response()->json(['ok' => true]);
    }
}

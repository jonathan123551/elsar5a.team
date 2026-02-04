<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Admin\BookingController;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    // ✅ Verify webhook
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

    // ✅ Handle incoming messages
    public function handle(Request $request)
    {
        $message = $request->input('entry.0.changes.0.value.messages.0');

        if (!$message || !isset($message['from'])) {
            return response()->json(['ok' => true]);
        }

        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        Log::info('WHATSAPP INCOMING MESSAGE', [
            'from' => $phone,
            'message' => $message,
        ]);

        /**
         * نجيب آخر حجز:
         * - نفس الرقم
         * - Approved
         * - فيه QR
         * - التذكرة متبعتتش قبل كده
         */
        $booking = Booking::where('phone', 'like', "%$phone%")
            ->where('status', 'approved')
            ->whereNotNull('qr_code_path')
            ->where(function ($q) {
                $q->whereNull('whatsapp_sent')
                  ->orWhere('whatsapp_sent', false);
            })
            ->latest()
            ->first();

        // لو التذكرة اتبعت قبل كده → تجاهل
        if (!$booking) {
            Log::info('NO VALID BOOKING OR TICKET ALREADY SENT', [
                'phone' => $phone,
            ]);
            return response()->json(['ok' => true]);
        }

        // ✅ ابعت التذكرة
        app(BookingController::class)->sendWhatsAppTicket(
            $booking->phone,
            $booking->qr_code_path,
            $booking->reference_code,
            $booking->full_name
        );

        // ✅ علّم إن التذكرة اتبعت
        $booking->update([
            'whatsapp_sent'    => true,
            'whatsapp_sent_at' => now(),
        ]);

        Log::info('TICKET SENT SUCCESSFULLY', [
            'booking_id' => $booking->id,
            'phone' => $phone,
        ]);

        return response()->json(['ok' => true]);
    }
}

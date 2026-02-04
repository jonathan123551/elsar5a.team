<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Admin\BookingController;

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

        // آخر حجز فيه QR ولسه التذكرة متبعتتش
        $booking = Booking::where('phone', 'like', "%$phone%")
            ->whereNotNull('qr_code_path')
            ->whereNull('ticket_sent_at')
            ->latest()
            ->first();

        // لو التذكرة اتبعت قبل كده → تجاهل
        if (!$booking) {
            return response()->json(['ok' => true]);
        }

        // ابعت التذكرة
        app(BookingController::class)->sendWhatsAppTicket(
            $booking->phone,
            $booking->qr_code_path,
            $booking->reference_code,
            $booking->full_name
        );

        // علّم إن التذكرة اتبعت
        $booking->update([
            'ticket_sent_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}

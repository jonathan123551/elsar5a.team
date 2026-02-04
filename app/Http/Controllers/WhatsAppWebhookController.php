<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Admin\BookingController;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    // ✅ VERIFY (دي أهم حتة)
    public function verify(Request $request)
    {
        if (
            $request->query('hub_mode') === 'subscribe' &&
            $request->query('hub_verify_token') === env('WHATSAPP_VERIFY_TOKEN')
        ) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    // ✅ HANDLE INCOMING MESSAGES
    public function handle(Request $request)
    {
        Log::info('WEBHOOK HIT', $request->all());

        $message = $request->input('entry.0.changes.0.value.messages.0');

        if (!$message || !isset($message['from'])) {
            return response()->json(['ok' => true]);
        }

        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        Log::info('WHATSAPP MESSAGE IN', [
            'from' => $phone,
            'message' => $message,
        ]);

        $booking = Booking::where('phone', 'like', "%$phone%")
            ->where('status', 'approved')
            ->whereNotNull('qr_code_path')
            ->where(function ($q) {
                $q->whereNull('whatsapp_sent')
                  ->orWhere('whatsapp_sent', false);
            })
            ->latest()
            ->first();

        if (!$booking) {
            Log::info('NO BOOKING OR ALREADY SENT', ['phone' => $phone]);
            return response()->json(['ok' => true]);
        }

        // ✅ ابعت التذكرة
        app(BookingController::class)->sendWhatsAppTicket(
            $booking->phone,
            $booking->qr_code_path,
            $booking->reference_code,
            $booking->full_name
        );

        // ✅ امنع الإعادة
        $booking->update([
            'whatsapp_sent'    => true,
            'whatsapp_sent_at' => now(),
        ]);

        Log::info('TICKET SENT SUCCESSFULLY', [
            'booking_id' => $booking->id,
        ]);

        return response()->json(['ok' => true]);
    }
}

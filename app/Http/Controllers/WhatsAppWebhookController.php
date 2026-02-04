<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Admin\BookingController;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
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

  public function handle(Request $request)
{
    Log::info('WEBHOOK HIT', $request->all());

    $message = $request->input('entry.0.changes.0.value.messages.0');

    if (!$message || !isset($message['from'])) {
        return response()->json(['ok' => true]);
    }

    $phone = preg_replace('/[^0-9]/', '', $message['from']);
    $text  = strtolower(trim($message['text']['body'] ?? ''));

    Log::info('MESSAGE FROM USER', [
        'phone' => $phone,
        'text'  => $text,
    ]);

    // ✅ VALIDATION
    $allowedMessages = [
        'استلام',
        'استلام التذكرة',
        'ticket',
        'get ticket'
    ];

    if (!in_array($text, $allowedMessages)) {
        Log::info('IGNORED MESSAGE', ['text' => $text]);
        return response()->json(['ok' => true]);
    }

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

    app(BookingController::class)->sendWhatsAppTicket(
        $booking->phone,
        $booking->qr_code_path,
        $booking->reference_code,
        $booking->full_name
    );

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

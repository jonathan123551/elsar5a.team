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
    $text  = trim($message['text']['body'] ?? '');

    Log::info('MESSAGE FROM USER', [
        'phone' => $phone,
        'text'  => $text,
    ]);

    /**
     * ✅ 1️⃣ السماح برسالة واحدة فقط
     */
    if ($text !== 'استلم التذكرة') {
        Log::info('IGNORED MESSAGE (NOT VALID KEYWORD)', [
            'text' => $text
        ]);
        return response()->json(['ok' => true]);
    }

    /**
     * ✅ 2️⃣ نجيب الحجز سواء اتبعت أو لأ
     */
    $booking = Booking::where('phone', 'like', "%$phone%")
        ->where('status', 'approved')
        ->whereNotNull('qr_code_path')
        ->latest()
        ->first();

    if (!$booking) {
        Log::info('NO BOOKING FOUND', ['phone' => $phone]);
        return response()->json(['ok' => true]);
    }

    /**
     * ✅ 3️⃣ لو التذكرة اتبعت قبل كده → خلاص
     */
    if ($booking->whatsapp_sent) {
        Log::info('TICKET ALREADY SENT - BLOCKED', [
            'booking_id' => $booking->ALK->id
        ]);
        return response()->json(['ok' => true]);
    }

    /**
     * ✅ 4️⃣ إرسال التذكرة مرة واحدة فقط
     */
    app(BookingController::class)->sendWhatsAppTicket(
        $booking->phone,
        $booking->qr_code_path,
        $booking->reference_code,
        $booking->full_name,
        $booking->showTime
    );

    /**
     * ✅ 5️⃣ قفل الإرسال نهائي
     */
    $booking->update([
        'whatsapp_sent'    => true,
        'whatsapp_sent_at' => now(),
    ]);

    Log::info('TICKET SENT SUCCESSFULLY (ONCE)', [
        'booking_id' => $booking->id,
    ]);

    return response()->json(['ok' => true]);
}


}

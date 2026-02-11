<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

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
        // جلب أول رسالة
        $message = $request->input('entry.0.changes.0.value.messages.0');

        if (!$message || !isset($message['from'])) {
            return response()->json(['ok' => true]);
        }

        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        // دعم Text + Button
        $text = $message['text']['body'] 
            ?? $message['button']['text'] 
            ?? '';

        if (trim($text) !== 'استلم التذكرة') {
            return response()->json(['ok' => true]);
        }

        // البحث عن آخر حجز معتمد
        $booking = Booking::with('showTime')
            ->where('phone', 'like', "%$phone%")
            ->where('status', 'approved')
            ->whereNotNull('qr_code_path')
            ->latest()
            ->first();

        if (!$booking) {
            return response()->json(['ok' => true]);
        }

        // تجهيز موعد العرض
        $showTimeText = $booking->showTime
            ? $booking->showTime->date->format('d/m/Y') . ' • ' .
              \Carbon\Carbon::parse($booking->showTime->time)->format('h:i A')
            : 'سيتم إبلاغك بالموعد';

        // إرسال التذكرة
        app(\App\Http\Controllers\Admin\BookingController::class)
            ->sendWhatsAppTicket(
                $booking->phone,
                $booking->qr_code_path,
                $booking->reference_code,
                $booking->full_name,
                $showTimeText
            );

        // تسجيل أنه تم الإرسال
        if (!$booking->whatsapp_sent) {
            $booking->update([
                'whatsapp_sent'    => true,
                'whatsapp_sent_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}

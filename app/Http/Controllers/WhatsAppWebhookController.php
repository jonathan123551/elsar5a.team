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
    \Log::info('WEBHOOK HIT', $request->all());

    $message = $request->input('entry.0.changes.0.value.messages.0');

    if (!$message || !isset($message['from'])) {
        return response()->json(['ok' => true]);
    }

    $phone = preg_replace('/[^0-9]/', '', $message['from']);

    // ✅ دعم Text + Button
    $text = '';
    if (isset($message['text']['body'])) {
        $text = trim($message['text']['body']);
    }
    if (isset($message['button']['text'])) {
        $text = trim($message['button']['text']);
    }

    \Log::info('MESSAGE FROM USER', [
        'phone' => $phone,
        'text'  => $text,
    ]);

    if ($text !== 'استلم التذكرة') {
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

    // ✅ تجهيز موعد الحفلة
    $showTimeText = $booking->showTime
        ? $booking->showTime->date->format('d/m/Y') . ' • ' .
          \Carbon\Carbon::parse($booking->showTime->time)->format('h:i A')
        : 'سيتم إبلاغك بالموعد';

    app(\App\Http\Controllers\Admin\BookingController::class)
        ->sendWhatsAppTicket(
            $booking->phone,
            $booking->qr_code_path,
            $booking->reference_code,
            $booking->full_name,
            $showTimeText
        );

    // تسجيل الاستلام
    if (!$booking->whatsapp_sent) {
        $booking->update([
            'whatsapp_sent'    => true,
            'whatsapp_sent_at' => now(),
        ]);
    }

    return response()->json(['ok' => true]);
}





}

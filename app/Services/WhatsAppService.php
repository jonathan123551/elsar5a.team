<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public static function sendTicket($phone, $imageUrl, $reference)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        return Http::withToken(env('WHATSAPP_TOKEN'))
            ->post(
                'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'image',
                    'image' => [
                        'link' => $imageUrl,
                        'caption' =>
                            "🎟️ تم تأكيد حجزك\n\n"
                            . "رقم الحجز: {$reference}\n"
                            . "يرجى إحضار هذه التذكرة عند الدخول ❤️",
                    ],
                ]
            );
    }
}

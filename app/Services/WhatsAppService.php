<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public static function sendTicket($phone, $imageUrl, $reference,$full_name)
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
                                    "🎟️ تم تأكيد حجزك بنجاح {$full_name}\n"
                                    . "نحن سعداء بوجودك ضمن جمهور فريق الصرخة المسرحي.\n"
                                    . "نتمنالك أمسية مليئة بالفن، و الوعي، والصراخ ✨\n"
                                    . "فنحن لا نريد سوى حواسِّكم.\n"
                                    . "وكل ما نحتاجه منكم هو أن تأتوا إلى مصدر الصراخ…\n"
                                    . "فدائمًا يكون على المسرح 🎭\n"
                                    . " ❤️*نجول نصرخ فيزداد العقل وعيا*\n\n"
                                    . "يرجى إحضار هذه التذكرة عند الدخول❤️🎭،\n"
                                    ],
                ]
            );
    }
}

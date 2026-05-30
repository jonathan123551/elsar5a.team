<?php

namespace App\Services;

use App\Models\Ticket;
use App\Support\CloudinaryUrl;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound-WhatsApp helper.
 *
 * Extracted from Admin\BookingController so the same code paths (template
 * send for approved bookings, image send for ticket delivery) can be invoked
 * from queue jobs without an HTTP request context. The network-call shape,
 * the Cloudinary URL rewrite, the template name, and the caption text are all
 * identical to what shipped before — the methods just live here now so a
 * `queue:work` worker can call them.
 */
class TicketDeliveryService
{
    /**
     * Send the approved-booking confirmation TEMPLATE message (no media —
     * the customer taps "أستلام التذكرة" to trigger the image send via the
     * webhook). Matches the existing sendTicketTemplate() contract verbatim.
     */
    public function sendTemplate(string $phone): Response
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
            'https://graph.facebook.com/v23.0/'.env('WHATSAPP_PHONE_ID').'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => 'ticket_clean_final',
                    'language' => ['code' => 'ar_EG'],
                    'components' => [],
                ],
            ]
        );

        Log::info('WA OUTBOUND TEMPLATE', [
            'phone' => $phone,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
            'body_raw' => $response->body(),
        ]);

        return $response;
    }

    /**
     * Send the ticket QR image with the existing caption. The image URL is
     * rewritten through CloudinaryUrl::forWhatsApp() so Meta fetches a
     * downsized JPEG instead of the raw high-res asset (Meta drops > 5 MB).
     */
    public function sendImage(Ticket $ticket): Response
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $ticket->phone);

        $imageUrl = CloudinaryUrl::forWhatsApp((string) $ticket->qr_image_path);

        // Eager-load the relationship the caption needs so a queue worker
        // (no request-scoped cache) doesn't lazy-query inside the formatter.
        $ticket->loadMissing(['booking.showTime']);

        $caption = $this->buildTicketCaption($ticket);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
            'https://graph.facebook.com/v23.0/'.env('WHATSAPP_PHONE_ID').'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                    'caption' => $caption,
                ],
            ]
        );

        Log::info('WA OUTBOUND IMAGE', [
            'phone' => $phone,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
            'body_raw' => $response->body(),
            'link' => $imageUrl,
        ]);

        return $response;
    }

    /**
     * Builds the ticket caption sent next to the QR image. Only the customer
     * name and the show date/time are dynamic — the brand-voice flavour text
     * is kept verbatim from the original sendWhatsAppTicket().
     */
    private function buildTicketCaption(Ticket $ticket): string
    {
        $fullName = trim((string) $ticket->name);
        $showTime = $ticket->booking?->showTime;

        $showTimeText = '';
        if ($showTime) {
            $date = $showTime->date ? $showTime->date->format('d/m/Y') : '';
            $time = $showTime->time ? Carbon::parse($showTime->time)->format('h:i A') : '';
            $showTimeText = trim($date.($date && $time ? ' • ' : '').$time);
        }

        return "*🎟️ أهلاً {$fullName}*\n\n"
            ."يسعدنا وجودك معنا،\n"
            ."أنت الآن جزء من تجربة جديدة نصرخ فيها سويًا…\n\n"
            ."ليزداد العقل وعيًا.\n\n"
            ."نتمنى لك أمسية ثرية بالفن ✨\n\n"
            ."نحن لا نطلب منك سوى حواسك،\n"
            ."ولا ننتظر منك إلا أن تأتي إلى مصدر الصراخ…\n"
            ."فهو دائمًا على المسرح 🎭\n\n"
            ."نلتقي لنصرخ معًا،\n"
            ."فنغيّر ما فسد،\n"
            ."ونزرع بدلًا منه ثمرًا صالحًا ❤️\n\n"
            ."🗓️ *موعد الحفلة:*\n"
            ."{$showTimeText}\n\n"
            .'‼️ *يرجى إحضار هذه التذكرة عند الدخول*';
    }
}

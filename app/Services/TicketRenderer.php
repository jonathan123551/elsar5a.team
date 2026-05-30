<?php

namespace App\Services;

use App\Models\Ticket;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;

/**
 * Builds the per-ticket QR, composites it onto the show's ticket template,
 * uploads the result to Cloudinary, and stamps the URL onto the ticket row.
 * Extracted verbatim from Admin\BookingController::approve()'s image loop so
 * the same pipeline runs from a queue job instead of blocking the admin's
 * HTTP request.
 *
 * Idempotent on `qr_image_path`: if the ticket already has a URL (e.g. the
 * job is retried after a partial failure), the existing image is reused —
 * we never re-upload a duplicate to Cloudinary.
 */
class TicketRenderer
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    /**
     * Build → composite → upload → persist. Returns the secure_url of the
     * Cloudinary asset now stored on the ticket row, or null if the show
     * has no ticket template configured yet.
     */
    public function renderAndUpload(Ticket $ticket): ?string
    {
        if ($ticket->qr_image_path) {
            return $ticket->qr_image_path;
        }

        $show = $ticket->booking?->showTime?->show;
        if (! $show || ! $show->ticket_template_path) {
            return null;
        }

        // A 2000×2000 RGB bitmap is ~16 MB; bump the soft limit defensively
        // (mirrors the original approve() guard).
        @ini_set('memory_limit', '512M');

        $qr = Builder::create()
            ->writer(new PngWriter)
            ->data($ticket->ticket_code)
            ->size($show->ticket_qr_size ?? 220)
            ->margin(0)
            ->build();

        // Pull the template through a Cloudinary transform that downscales the
        // long edge to 2000 px and re-encodes to JPEG — caps RAM/download cost.
        $templateUrl = $show->ticket_template_path;
        if (str_contains($templateUrl, '/image/upload/')) {
            $templateUrl = preg_replace(
                '#/image/upload/#',
                '/image/upload/c_limit,w_2000,h_2000,q_auto:good,f_jpg/',
                $templateUrl,
                1
            );
        }

        $templateBytes = @file_get_contents($templateUrl);
        if ($templateBytes === false) {
            $templateBytes = @file_get_contents($show->ticket_template_path);
        }

        $templateImage = $templateBytes ? @imagecreatefromstring($templateBytes) : false;
        if (! $templateImage) {
            Log::warning('TicketRenderer: failed to load ticket template', [
                'ticket' => $ticket->ticket_code,
                'url' => $show->ticket_template_path,
            ]);

            return null;
        }

        $qrImage = imagecreatefromstring($qr->getString());

        imagecopy(
            $templateImage,
            $qrImage,
            $show->ticket_qr_x ?? 0,
            $show->ticket_qr_y ?? 0,
            0,
            0,
            imagesx($qrImage),
            imagesy($qrImage)
        );

        // JPEG q=92: keeps the QR crisp while landing ~300-800 KB, under
        // WhatsApp's ~5 MB silent-drop cap (the original PNG path produced
        // 5-15 MB files that Meta dropped).
        $tempPath = sys_get_temp_dir().'/'.$ticket->ticket_code.'.jpg';
        imagejpeg($templateImage, $tempPath, 92);

        imagedestroy($templateImage);
        imagedestroy($qrImage);

        $upload = (new UploadApi)->upload($tempPath, [
            'folder' => 'tickets/generated',
        ]);

        @unlink($tempPath);

        $secureUrl = $upload['secure_url'] ?? null;

        if ($secureUrl) {
            // whatsapp_sent stays false: the image is ready but not delivered
            // yet. SendWhatsAppTicketImageJob flips that flag once Meta acks.
            $ticket->update([
                'qr_image_path' => $secureUrl,
                'whatsapp_sent' => false,
            ]);
        }

        return $secureUrl;
    }
}

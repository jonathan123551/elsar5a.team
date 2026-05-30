<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Per-ticket image-build job: QR + composite onto the show template +
 * Cloudinary upload. Dispatched from Admin\BookingController::approve()
 * so the admin's request returns immediately instead of blocking on
 * GD + Cloudinary for every ticket.
 *
 * It intentionally DOES NOT send the WhatsApp image. The actual image
 * send is gated on the customer tapping "استلام التذكرة" (Meta's 24h
 * customer-service window only opens after that inbound message), so
 * delivery is driven by WhatsAppWebhookController -> SendWhatsAppTicketImageJob.
 * This job just makes sure the image is ready and uploaded ahead of the tap.
 *
 * Idempotent on qr_image_path: a retry after a partial failure reuses the
 * existing Cloudinary URL instead of uploading a duplicate.
 */
class GenerateTicketImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 15, 60];
    }

    public function __construct(public int $ticketId) {}

    public function handle(TicketRenderer $renderer): void
    {
        $ticket = Ticket::with(['booking.showTime.show'])->find($this->ticketId);

        if (! $ticket) {
            Log::warning('GenerateTicketImageJob: ticket not found', ['ticket_id' => $this->ticketId]);

            return;
        }

        $url = $renderer->renderAndUpload($ticket);

        if (! $url) {
            // No ticket template configured on the show yet — same fail-soft
            // behaviour as the original guard in approve(). The send job will
            // try to render again when the customer taps, so nothing is lost.
            Log::info('GenerateTicketImageJob: skipped (no template)', ['ticket_id' => $this->ticketId]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateTicketImageJob: failed', [
            'ticket_id' => $this->ticketId,
            'error' => $e->getMessage(),
        ]);
    }
}

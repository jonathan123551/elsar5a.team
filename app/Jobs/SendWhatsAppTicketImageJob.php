<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketDeliveryService;
use App\Services\TicketRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Per-ticket WhatsApp image-send job. One job == one ticket, so a 50-ticket
 * booking on the same number fans out to 50 independent jobs that the
 * single-concurrency queue worker drains in order (replacing the old
 * in-request sleep(1) pacing).
 *
 * DUPLICATE PROTECTION (the core fix):
 *   handle() begins with an ATOMIC COMPARE-AND-SET claim:
 *
 *     UPDATE tickets SET delivery_status='sending'
 *      WHERE id=? AND whatsapp_sent=false
 *        AND delivery_status IN ('pending','failed')
 *
 *   Only the worker whose UPDATE affects 1 row proceeds to send. Two
 *   concurrent webhook taps, a Meta webhook retry, or two workers can
 *   never both claim the same ticket, so the same QR is never sent twice.
 *
 * MISSING-TICKET PROTECTION:
 *   • whatsapp_sent flips to TRUE only on a confirmed Meta ack — never on
 *     a mere dispatch. A retryable failure resets the row to 'failed' so it
 *     stays eligible for re-claim (queue retry, resend, or next tap).
 *   • If the image isn't built yet (customer tapped before generation
 *     finished) it renders inline here, so a tap never silently no-ops.
 */
class SendWhatsAppTicketImageJob implements ShouldQueue
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

    public function handle(TicketRenderer $renderer, TicketDeliveryService $delivery): void
    {
        // Atomic claim. If another worker / retry already owns or finished
        // this ticket, $claimed is 0 and we stop — no duplicate send.
        $claimed = Ticket::where('id', $this->ticketId)
            ->where('whatsapp_sent', false)
            ->whereIn('delivery_status', ['pending', 'failed'])
            ->update(['delivery_status' => 'sending']);

        if ($claimed === 0) {
            Log::info('SendWhatsAppTicketImageJob: not claimable (already sent / in-flight)', [
                'ticket_id' => $this->ticketId,
            ]);

            return;
        }

        $ticket = Ticket::with(['booking.showTime'])
            ->find($this->ticketId);

        if (! $ticket) {
            Log::warning('SendWhatsAppTicketImageJob: ticket vanished after claim', [
                'ticket_id' => $this->ticketId,
            ]);

            return;
        }

        // Build-if-missing: covers the race where the customer taps before
        // GenerateTicketImageJob finished. Idempotent on qr_image_path.
        if (! $ticket->qr_image_path) {
            $url = $renderer->renderAndUpload($ticket);

            if (! $url) {
                // No template configured yet — release the claim so a later
                // attempt can re-try once the show is set up.
                $ticket->update(['delivery_status' => 'pending']);
                Log::info('SendWhatsAppTicketImageJob: released, image not ready (no template)', [
                    'ticket_id' => $this->ticketId,
                ]);

                return;
            }

            $ticket->refresh();
        }

        $response = $delivery->sendImage($ticket);

        if ($response->successful() && ! $response->json('error')) {
            $ticket->update([
                'whatsapp_sent' => true,
                'delivery_status' => 'sent',
            ]);

            return;
        }

        // Permanent Meta errors: recipient not on WA (131026), outside the
        // 24h window (131047), media download error (131053). Don't burn the
        // retry budget — fail now, leaving whatsapp_sent=false for a resend.
        $errorCode = $response->json('error.code');
        $permanent = in_array($errorCode, [131026, 131047, 131053], true);

        $ticket->update(['delivery_status' => 'failed']);

        if ($permanent) {
            $this->fail(new \RuntimeException(
                'Permanent Meta error '.$errorCode.' — '.$response->body()
            ));

            return;
        }

        // Retryable — throw so the worker schedules the next attempt. The
        // row is back at 'failed', so the retry (or a fresh tap) re-claims it.
        throw new \RuntimeException(
            'WhatsApp image send failed (status '.$response->status().'): '.$response->body()
        );
    }

    public function failed(\Throwable $e): void
    {
        // Terminal failure: never leave a ticket stranded as 'sending'.
        // Reset to 'failed' (only if still undelivered) so the admin /
        // customer resend path can re-claim and try again.
        Ticket::where('id', $this->ticketId)
            ->where('whatsapp_sent', false)
            ->update(['delivery_status' => 'failed']);

        Log::error('SendWhatsAppTicketImageJob: failed', [
            'ticket_id' => $this->ticketId,
            'error' => $e->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Services\TicketDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Per-phone WhatsApp template-send job. Replaces the synchronous
 * sendTicketTemplate($phone) loop in Admin\BookingController::approve()
 * that used to dispatch the "ticket" template message to each ticket's
 * phone with a 1-second `sleep()` between calls to dodge Meta rate
 * limits. Single-concurrency queue worker gives us the same pacing
 * with zero in-request wait.
 */
class SendWhatsAppTicketTemplateJob implements ShouldQueue
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

    public function __construct(public string $phone) {}

    public function handle(TicketDeliveryService $delivery): void
    {
        $response = $delivery->sendTemplate($this->phone);

        if ($response->successful()) {
            return;
        }

        $errorCode = $response->json('error.code');
        $permanent = in_array($errorCode, [131026, 131047], true);

        if ($permanent) {
            $this->fail(new \RuntimeException(
                'Permanent Meta error '.$errorCode.' — '.$response->body()
            ));

            return;
        }

        throw new \RuntimeException(
            'WhatsApp template send failed (status '.$response->status().'): '.$response->body()
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendWhatsAppTicketTemplateJob: failed', [
            'phone' => $this->phone,
            'error' => $e->getMessage(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Controllers\Admin\BookingController;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta verification
     */
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

    /**
     * Handle incoming messages
     */
    public function handle(Request $request)
    {
        $entry = $request->input('entry.0.changes.0.value.messages.0');

        if (!$entry) {
            return response()->json(['ok' => true]);
        }

        // Quick Reply button
        if (
            isset($entry['button']['text']) &&
            $entry['button']['text'] === 'استلم التذكرة'
        ) {
            $phone = preg_replace('/[^0-9]/', '', $entry['from']);

            $booking = Booking::where('phone', 'like', "%$phone%")
                ->whereNotNull('qr_code_path')
                ->latest()
                ->first();

            if ($booking) {
                app(BookingController::class)
                    ->sendWhatsAppTicket($booking);
            }
        }

        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ScannerController extends Controller
{
    /**
     * Render the gate scanner UI.
     *
     * Intentionally returns a minimal blade view that boots the
     * premium scanner stack (multi-engine decode + cinematic result
     * sheet). The page itself is reachable without admin auth so
     * staff can run the scanner on shared devices at the door — only
     * a single validated ticket's name / phone / show metadata is
     * echoed at a time, no booking management is exposed.
     */
    public function index()
    {
        return view('admin.scanner');
    }

    /**
     * Validate a scanned QR code and (on first scan) mark the ticket
     * as used.
     *
     * Adapted from the Joseph Nabil scanner controller — the only
     * structural difference is that Elsar5a.Team does not have a
     * BookingSeat / Seat model, so the per-ticket seat identity
     * fields (`seat`, `seats`, `sections`) are simply omitted. The
     * front-end's result sheet treats every missing field as
     * optional, so the popup remains pixel-identical to joseph-nabil
     * for the fields this repo does carry (name, phone, show, date,
     * time, reference, scanned_at).
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = trim($data['code']);

        $ticket = Ticket::with('booking.showTime.show')
            ->where('ticket_code', $code)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير موجود',
            ]);
        }

        if (!$ticket->booking || $ticket->booking->status !== 'approved') {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير معتمد',
            ]);
        }

        $booking = $ticket->booking;
        $time    = $booking->showTime;

        $payload = [
            'name'          => $ticket->name,
            'phone'         => $ticket->phone,
            'show_title'    => optional(optional($time)->show)->title ?? '',
            'date'          => optional(optional($time)->date)->format('d/m/Y'),
            'time'          => optional($time)->time
                ? Carbon::parse($time->time)->format('g:i A')
                : '',
            'show_time_id'  => $time ? (int) $time->id : null,
            'tickets_count' => (int) ($booking->tickets_count ?? 0),
            'reference'     => $booking->reference_code ?? '',
            'scanned_at'    => $ticket->scanned_at
                ? Carbon::parse($ticket->scanned_at)->format('g:i A')
                : null,
        ];

        // Already scanned — return the same enriched payload but
        // flagged as "used" so the front-end shows the amber state.
        if ($ticket->scanned_at) {
            return response()->json(array_merge([
                'status'  => 'used',
                'message' => 'تم استخدامها',
            ], $payload));
        }

        // First successful scan — mark the ticket and respond with
        // the freshly-stamped scan time.
        $ticket->scanned_at = now();
        $ticket->is_scanned = true;
        $ticket->save();

        $payload['scanned_at'] = now()->format('g:i A');

        return response()->json(array_merge([
            'status'  => 'ok',
            'message' => 'دخول مسموح',
        ], $payload));
    }
}

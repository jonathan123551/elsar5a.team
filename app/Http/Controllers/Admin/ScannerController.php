<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ScannerController extends Controller
{
    /**
     * Render the gate scanner UI.
     *
     * The page is intentionally reachable without admin auth so door
     * staff can run the scanner on shared devices at the door. Only
     * a single validated ticket's name / phone / show metadata is
     * echoed at a time, no booking management is exposed, and the
     * POST /admin/scanner/check endpoint is rate-limited so a burst
     * of guessed codes can't burn tickets faster than a real
     * operator scans them. There is intentionally NO PIN gate — the
     * real-world organizer entrance flow has to stay ultra-fast.
     */
    public function index()
    {
        return view('admin.scanner');
    }

    /**
     * Validate a scanned QR code and (on first scan) mark the ticket
     * as used.
     *
     * Reliability fix: the original implementation read the ticket,
     * branched on $ticket->scanned_at, then wrote a fresh scanned_at
     * with a separate save() — there was no row lock, so two
     * near-simultaneous scans (or two scanners on the same device)
     * could both observe scanned_at = null and both record
     * themselves as the "first" scan. We now run the read + branch
     * + write inside a transaction with lockForUpdate(), so the
     * second concurrent scanner sees the freshly stamped row and
     * returns the "used" branch instead of overwriting.
     *
     * We also (optionally) record the scanning device's IP + UA on
     * the ticket so a burnt ticket can be traced back to a specific
     * device. The columns are written only if they exist, so this
     * keeps working on environments where the migration hasn't been
     * applied yet.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:128'],
        ]);

        $code = trim($data['code']);

        $result = DB::transaction(function () use ($code, $request) {

            $ticket = Ticket::with('booking.showTime.show')
                ->where('ticket_code', $code)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                return [
                    'status'  => 'error',
                    'message' => 'غير موجود',
                ];
            }

            if (!$ticket->booking || $ticket->booking->status !== 'approved') {
                return [
                    'status'  => 'error',
                    'message' => 'غير معتمد',
                ];
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

            if ($ticket->scanned_at) {
                return array_merge([
                    'status'  => 'used',
                    'message' => 'تم استخدامها',
                ], $payload);
            }

            $ticket->scanned_at = now();
            $ticket->is_scanned = true;

            if (Schema::hasColumn($ticket->getTable(), 'scanned_by_ip')) {
                $ticket->scanned_by_ip = $request->ip();
            }
            if (Schema::hasColumn($ticket->getTable(), 'scanned_by_ua')) {
                $ticket->scanned_by_ua = substr((string) $request->userAgent(), 0, 250);
            }

            $ticket->save();

            $payload['scanned_at'] = now()->format('g:i A');

            return array_merge([
                'status'  => 'ok',
                'message' => 'دخول مسموح',
            ], $payload);
        });

        return response()->json($result);
    }
}

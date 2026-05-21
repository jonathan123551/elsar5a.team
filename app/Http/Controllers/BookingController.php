<?php

namespace App\Http\Controllers;

use App\Models\ShowTime;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use App\Support\UploadCompressor;

class BookingController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    // ================= CREATE =================
    public function create(ShowTime $showTime)
    {
        $reserved = $showTime->bookings()
            ->whereIn('status', ['approved', 'pending'])
            ->sum('tickets_count');

        $remaining = max(0, $showTime->total_tickets - $reserved);

        abort_if($remaining <= 0 || $showTime->is_sold_out, 404);

        return view('bookings.create', [
            'showTime'       => $showTime,
            'transferWallet' => Setting::get('transfer_wallet', ''),
            'transferInsta'  => Setting::get('transfer_insta', ''),
            'remaining'      => $remaining,
        ]);
    }

    // ================= STORE =================
    /**
     * Create a booking for the given showtime.
     *
     * Concurrency / double-submit protection (layered):
     *
     * 1) Idempotency token. The booking form ships a client-generated
     *    `idempotency_token` (stable across refreshes via
     *    sessionStorage). We Cache::add() it for 10 minutes; if the
     *    same token POSTs again we short-circuit to the
     *    "already-processed" branch instead of double-charging the
     *    customer. The reservation is released in `finally` on any
     *    failure path so the user can retry; it is kept only on
     *    success so a refresh/back-button replay is still rejected.
     *
     * 2) Short-lived per-IP/phone lock. Catches the case where a
     *    customer opens two tabs simultaneously (different tokens
     *    but same phone/IP) and submits both within a few seconds.
     *
     * 3) Transactional capacity check with a row-level lock on the
     *    showtime row. The outer SELECT FOR UPDATE serializes any
     *    concurrent booking attempts for THIS showtime, so a plain
     *    aggregate ->sum() inside the transaction is safe. We
     *    deliberately do NOT chain ->lockForUpdate() onto the sum
     *    query — PostgreSQL rejects `SELECT FOR UPDATE` combined
     *    with aggregate functions ("FOR UPDATE is not allowed with
     *    aggregate functions"), which would 500 every booking
     *    attempt on a Postgres backend.
     *
     * All branches inside the transaction return a tagged array
     * instead of calling abort(redirect()) — throwing redirects
     * across the DB::transaction boundary is fragile and was
     * surfacing the themed 500 page on every failure mode.
     */
    public function store(Request $request, ShowTime $showTime)
    {
        // ---- 1) Idempotency token ----------------------------------
        $idempotencyToken = (string) $request->input('idempotency_token', '');
        $idempotencyCacheKey = null;

        if ($idempotencyToken !== '' && strlen($idempotencyToken) <= 64) {
            $idempotencyCacheKey = 'booking_idem_' . sha1($idempotencyToken . '|' . $showTime->id);
            if (!Cache::add($idempotencyCacheKey, true, now()->addMinutes(10))) {
                return back()->withErrors([
                    'general' => '⏳ هذا الطلب تمت معالجته بالفعل — يرجى الانتظار قليلًا.',
                ])->withInput();
            }
        }

        // ---- 2) Per-IP/phone short lock ----------------------------
        $lockKey = 'booking_lock_' . sha1(
            $request->ip() . json_encode($request->phones ?? []) . $showTime->id
        );

        if (!Cache::add($lockKey, true, 20)) {
            // Release the idempotency reservation so the user's next
            // retry isn't permanently rejected as "already processed".
            if ($idempotencyCacheKey) {
                Cache::forget($idempotencyCacheKey);
            }
            return back()->withErrors([
                'general' => '⏳ الطلب قيد المعالجة بالفعل',
            ])->withInput();
        }

        $bookingSucceeded = false;

        try {

            // ✅ VALIDATION
            $request->validate([
                'names'              => ['required', 'array', 'min:1', 'max:20'],
                'names.*'            => ['required', 'string', 'max:255'],
                'phones'             => ['required', 'array', 'min:1', 'max:20'],
                'phones.*'           => ['required', 'string', 'min:8', 'max:20'],
                'payment_screenshot' => ['required', 'image', 'max:20480'],
                'idempotency_token'  => ['nullable', 'string', 'max:64'],
            ]);

            if (count($request->names) !== count($request->phones)) {
                return back()->withErrors([
                    'general' => 'عدد الأسماء لا يطابق عدد الأرقام.',
                ])->withInput();
            }

            $names  = $request->names;
            $phones = $request->phones;
            $ticketsCount = count($names);

            // Normalize phones up front so we fail fast (and return a
            // user-friendly validation error) before we burn a
            // Cloudinary upload and a DB row.
            $normalizedPhones = [];
            foreach ($phones as $p) {
                $normalizedPhones[] = $this->normalizeEgyptPhone($p);
            }

            // Cheap capacity pre-check before we burn a Cloudinary
            // upload — the authoritative re-check happens inside
            // the transaction below with a row-level lock.
            $preReserved = $showTime->bookings()
                ->whereIn('status', ['approved', 'pending'])
                ->sum('tickets_count');

            $preRemaining = max(0, $showTime->total_tickets - $preReserved);

            if ($showTime->is_sold_out || $preRemaining <= 0) {
                return back()->withErrors([
                    'general' => '❌ لا توجد تذاكر متاحة',
                ])->withInput();
            }

            if ($ticketsCount > $preRemaining) {
                return back()->withErrors([
                    'general' => '❌ المتاح فقط: ' . $preRemaining . ' تذاكر',
                ])->withInput();
            }

            // Upload the screenshot OUTSIDE the transaction so the
            // showtime row lock isn't held during a slow external
            // HTTP call to Cloudinary.
            //
            // BEFORE handing it to Cloudinary we downscale + JPEG
            // re-encode locally. A 12-megapixel iPhone screenshot
            // is ~5 MB raw; after compression it's typically
            // 300-600 KB at quality 82 / max 2000 px on the long
            // edge. That alone shaves ~80-95% off the time the user
            // spends staring at the spinner, and keeps the request
            // well under the worker's memory cap.
            $compressedPath = UploadCompressor::compress(
                $request->file('payment_screenshot'),
                maxEdge: 2000,
                quality: 82,
            );

            $upload = (new UploadApi())->upload(
                $compressedPath,
                ['folder' => 'payments/screenshots']
            );

            if ($compressedPath !== $request->file('payment_screenshot')->getRealPath()) {
                @unlink($compressedPath);
            }

            $result = DB::transaction(function () use (
                $showTime, $names, $normalizedPhones, $ticketsCount, $upload
            ) {
                // SELECT FOR UPDATE pinning the showtime row. This
                // is the ONLY lock we take — it's enough to
                // serialize concurrent booking attempts for this
                // showtime, and it lets us run a plain aggregate
                // sum() below (Postgres rejects FOR UPDATE with
                // aggregates).
                $locked = ShowTime::whereKey($showTime->id)
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->is_sold_out) {
                    return ['error' => '❌ هذا العرض مغلق حاليًا'];
                }

                $reserved = $locked->bookings()
                    ->whereIn('status', ['approved', 'pending'])
                    ->sum('tickets_count');

                $remaining = max(0, $locked->total_tickets - $reserved);

                if ($remaining <= 0) {
                    return ['error' => '❌ لا توجد تذاكر متاحة'];
                }

                if ($ticketsCount > $remaining) {
                    return ['error' => '❌ المتاح فقط: ' . $remaining . ' تذاكر'];
                }

                $b = Booking::create([
                    'show_time_id'                  => $locked->id,
                    'full_name'                     => $names[0],
                    'phone'                         => $normalizedPhones[0],
                    'tickets_count'                 => $ticketsCount,
                    'total_price'                   => $locked->ticket_price * $ticketsCount,
                    'transfer_screenshot_path'      => $upload['secure_url'],
                    'transfer_screenshot_public_id' => $upload['public_id'],
                    'status'                        => 'pending',
                    'reference_code'                => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                ]);

                foreach ($names as $i => $name) {
                    \App\Models\Ticket::create([
                        'booking_id'  => $b->id,
                        'name'        => $name,
                        'phone'       => $normalizedPhones[$i],
                        'ticket_code' => 'TIC-' . strtoupper(Str::random(6)),
                    ]);
                }

                return ['booking' => $b];
            });

            if (isset($result['error'])) {
                return back()->withErrors([
                    'general' => $result['error'],
                ])->withInput();
            }

            $bookingSucceeded = true;

            // PRG (Post-Redirect-Get): redirect to a dedicated GET
            // route so the thank-you page has its own URL, a refresh
            // doesn't re-POST the booking, and iOS Safari's bfcache
            // never shows the user a stale form-submit state.
            return redirect()->route('bookings.thankyou', [
                'reference' => $result['booking']->reference_code,
            ]);

        } finally {
            Cache::forget($lockKey);
            // Release the idempotency reservation on any failure
            // (validation error, capacity miss, transaction
            // rollback, exception, 500) so the user's next retry
            // isn't permanently rejected as "already processed".
            // On success we keep the reservation so a refresh /
            // back-button replay is still rejected cleanly.
            if (!$bookingSucceeded && $idempotencyCacheKey) {
                Cache::forget($idempotencyCacheKey);
            }
        }
    }

    // ================= THANK YOU =================
    public function thankyou(string $reference)
    {
        $booking = Booking::where('reference_code', $reference)->firstOrFail();

        return view('bookings.thankyou', compact('booking'));
    }

    private function normalizeEgyptPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^01[0-9]{9}$/', $phone)) {
            return '20' . substr($phone, 1);
        }

        if (preg_match('/^1[0-9]{9}$/', $phone)) {
            return '20' . $phone;
        }

        if (preg_match('/^20[0-9]{10}$/', $phone)) {
            return $phone;
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'phone' => 'رقم غير صحيح',
        ]);
    }
}

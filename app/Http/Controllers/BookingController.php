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
     *    customer.
     *
     * 2) Short-lived per-IP/phone lock. Catches the case where a
     *    customer opens two tabs simultaneously (different tokens
     *    but same phone/IP) and submits both within a few seconds.
     *
     * 3) Transactional capacity check with row-level lock on the
     *    showtime + pending/approved bookings. This closes the
     *    real race: two customers checking out simultaneously
     *    while only one ticket is left. Previously the code did a
     *    plain ->sum() outside any transaction, so both clients
     *    saw `remaining=1` and both got created. We now SELECT FOR
     *    UPDATE the showtime row, re-sum bookings inside the same
     *    transaction, then insert. The second concurrent submit
     *    sees the freshly committed count and is rejected cleanly.
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

        try {

            // ✅ VALIDATION
            $request->validate([
                'names'              => ['required', 'array', 'min:1', 'max:20'],
                'names.*'            => ['required', 'string', 'max:255'],
                'phones'             => ['required', 'array', 'min:1', 'max:20'],
                'phones.*'           => ['required', 'string', 'min:8', 'max:20'],
                'payment_screenshot' => ['required', 'image', 'max:16000'],
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

            // Upload the screenshot OUTSIDE the transaction so the
            // showtime row lock isn't held during a slow external
            // HTTP call to Cloudinary. We hand Cloudinary the
            // already-validated temp upload path directly instead of
            // re-buffering the file into memory — important for
            // 8–16 MB iPhone screenshots on a 256 MB worker.
            $upload = (new UploadApi())->upload(
                $request->file('payment_screenshot')->getRealPath(),
                ['folder' => 'payments/screenshots']
            );

            $booking = DB::transaction(function () use (
                $showTime, $names, $normalizedPhones, $ticketsCount, $upload
            ) {
                // SELECT FOR UPDATE pinning the showtime row.
                $locked = ShowTime::whereKey($showTime->id)
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->is_sold_out) {
                    abort(redirect()->back()->withErrors([
                        'general' => '❌ هذا العرض مغلق حاليًا',
                    ])->withInput());
                }

                $reserved = $locked->bookings()
                    ->whereIn('status', ['approved', 'pending'])
                    ->lockForUpdate()
                    ->sum('tickets_count');

                $remaining = max(0, $locked->total_tickets - $reserved);

                if ($remaining <= 0) {
                    abort(redirect()->back()->withErrors([
                        'general' => '❌ لا توجد تذاكر متاحة',
                    ])->withInput());
                }

                if ($ticketsCount > $remaining) {
                    abort(redirect()->back()->withErrors([
                        'general' => '❌ المتاح فقط: ' . $remaining . ' تذاكر',
                    ])->withInput());
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

                return $b;
            });

            // PRG (Post-Redirect-Get): redirect to a dedicated GET
            // route so the thank-you page has its own URL, a refresh
            // doesn't re-POST the booking, and iOS Safari's bfcache
            // never shows the user a stale form-submit state.
            return redirect()->route('bookings.thankyou', [
                'reference' => $booking->reference_code,
            ]);

        } finally {
            Cache::forget($lockKey);
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

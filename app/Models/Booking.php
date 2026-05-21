<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    protected $fillable = [
        'show_time_id',
        'show_id',
        'public_number',
        'full_name',
        'phone',
        'tickets_count',
        'total_price',

        // الدفع
        'payment_method',
        'payment_status',
        'transfer_screenshot_path',
        'paid_at',

        // الحجز
        'status',
        'reference_code',
        'qr_code_path',
        'admin_notes',

        // الإدارة
        'approved_by_admin_id',
        'approved_at',

        // واتساب
        'whatsapp_sent',
        'whatsapp_sent_at',

        'transfer_screenshot_public_id',
        'qr_code_public_id',
    ];

    protected $casts = [
        'paid_at'           => 'datetime',
        'approved_at'       => 'datetime',
        'whatsapp_sent_at'  => 'datetime',
        'public_number'     => 'integer',
    ];

    /**
     * Atomically allocate the next public booking number for a given
     * show.
     *
     * The whole operation is one Postgres statement:
     *
     *   INSERT INTO show_booking_counters (...)
     *   VALUES (?, 1, NOW(), NOW())
     *   ON CONFLICT (show_id)
     *   DO UPDATE SET last_number = show_booking_counters.last_number + 1,
     *                 updated_at  = NOW()
     *   RETURNING last_number;
     *
     * Why this is safe:
     *   * The ON CONFLICT branch locks the counter row at the
     *     row level for the duration of the statement, so two
     *     concurrent bookings on the same show serialise — one
     *     returns N, the other returns N + 1.
     *   * The whole call runs inside the caller's transaction
     *     (BookingController@store opens one). If anything fails
     *     after this call but before commit, the counter
     *     increment is rolled back, so no numbers are burned by
     *     failed bookings.
     *   * The UNIQUE (show_id, public_number) constraint on
     *     `bookings` is a belt-and-braces safety net: if anything
     *     ever does attempt a duplicate (e.g. a manual SQL
     *     intervention), Postgres rejects the insert and the
     *     transaction rolls back.
     */
    public static function allocatePublicNumber(int $showId): int
    {
        $row = DB::selectOne(
            <<<'SQL'
            INSERT INTO show_booking_counters
                   (show_id, last_number, created_at, updated_at)
            VALUES (?, 1, NOW(), NOW())
            ON CONFLICT (show_id)
            DO UPDATE SET last_number = show_booking_counters.last_number + 1,
                          updated_at  = NOW()
            RETURNING last_number
            SQL,
            [$showId],
        );

        return (int) $row->last_number;
    }

    /**
     * Render-ready string for the booking number (`"#42"`).
     * Falls back to the DB id for any legacy row that somehow
     * still has a NULL public_number — should never happen after
     * the backfill migration, but defensive UI rendering keeps
     * the admin pages from showing "#" with nothing after it.
     */
    protected function displayNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => '#' . ($this->public_number ?? $this->id),
        );
    }

    public function showTime()
    {
        return $this->belongsTo(ShowTime::class);
    }

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot backfill for existing bookings.
 *
 * For every existing booking:
 *   1. Denormalise show_id from show_times.show_id.
 *   2. Assign public_number = 1, 2, 3, … per show, ordered by
 *      (created_at ASC, id ASC). Chronological ordering means the
 *      first booking ever placed for a show retroactively becomes
 *      #1 — matching the user's mental model of "Show A has 3
 *      bookings, numbered 1 through 3".
 *   3. Prime show_booking_counters so the live allocation logic
 *      picks up at N+1.
 *
 * Idempotent: the WHERE clauses only touch rows where the field
 * is still NULL, so re-running the migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('bookings') ||
            !Schema::hasTable('show_times') ||
            !Schema::hasTable('show_booking_counters') ||
            !Schema::hasColumn('bookings', 'show_id') ||
            !Schema::hasColumn('bookings', 'public_number')
        ) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::transaction(function () {
            // 1) Denormalise show_id onto bookings that don't have it yet.
            DB::statement(<<<'SQL'
                UPDATE bookings AS b
                   SET show_id = st.show_id
                  FROM show_times AS st
                 WHERE b.show_time_id = st.id
                   AND b.show_id IS NULL
            SQL);

            // 2) Assign sequential public_number per show, oldest first.
            //    Uses a single window-function update so the entire
            //    backfill runs as one statement per Postgres pass.
            DB::statement(<<<'SQL'
                UPDATE bookings AS b
                   SET public_number = ranked.rn
                  FROM (
                        SELECT id,
                               ROW_NUMBER() OVER (
                                   PARTITION BY show_id
                                   ORDER BY created_at ASC, id ASC
                               ) AS rn
                          FROM bookings
                         WHERE show_id IS NOT NULL
                           AND public_number IS NULL
                  ) AS ranked
                 WHERE b.id = ranked.id
            SQL);

            // 3) Prime the per-show counters. INSERT ... ON CONFLICT
            //    keeps this idempotent if the migration is re-run.
            DB::statement(<<<'SQL'
                INSERT INTO show_booking_counters (show_id, last_number, created_at, updated_at)
                SELECT show_id,
                       MAX(public_number) AS last_number,
                       NOW(),
                       NOW()
                  FROM bookings
                 WHERE show_id IS NOT NULL
                   AND public_number IS NOT NULL
                 GROUP BY show_id
                ON CONFLICT (show_id) DO UPDATE
                   SET last_number = GREATEST(
                           show_booking_counters.last_number,
                           EXCLUDED.last_number
                       ),
                       updated_at = NOW()
            SQL);
        });
    }

    public function down(): void
    {
        // Backfilled values are purely additive. The structural
        // rollback lives in the column-add migration; this one
        // intentionally leaves the data alone so a re-`up` is a
        // no-op rather than a destructive re-allocation that could
        // change historical numbers.
    }
};

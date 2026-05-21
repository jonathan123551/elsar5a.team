<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the public-facing booking number to `bookings`.
 *
 *   public_number   the integer shown to users / printed on tickets.
 *                   Allocated per show (e.g. Show A: #1, #2, #3,
 *                   Show B: #1, #2). Nullable here because the
 *                   backfill migration that follows assigns values
 *                   for existing rows.
 *
 *   show_id         denormalised from show_times.show_id. We carry
 *                   it on bookings so the per-show UNIQUE constraint
 *                   and the counter lookup don't have to JOIN
 *                   through show_times on every booking insert.
 *                   The denormalisation is safe because a booking's
 *                   show_time never changes — and even if it did,
 *                   it would still belong to the same show.
 *
 * The UNIQUE (show_id, public_number) constraint is a belt-and-
 * braces safety net on top of the counter table. If anything ever
 * tries to insert a duplicate, Postgres rejects the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'show_id')) {
                $table->unsignedBigInteger('show_id')->nullable()->after('show_time_id');
            }

            if (!Schema::hasColumn('bookings', 'public_number')) {
                $table->unsignedInteger('public_number')->nullable()->after('show_id');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1
                          FROM pg_constraint
                         WHERE conname = 'bookings_show_id_foreign'
                    ) THEN
                        ALTER TABLE bookings
                            ADD CONSTRAINT bookings_show_id_foreign
                            FOREIGN KEY (show_id)
                            REFERENCES shows(id)
                            ON DELETE SET NULL;
                    END IF;
                END
                $$;
            SQL);

            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS bookings_show_public_number_unique
                 ON bookings (show_id, public_number)
                 WHERE show_id IS NOT NULL AND public_number IS NOT NULL'
            );

            DB::statement(
                'CREATE INDEX IF NOT EXISTS bookings_public_number_index
                 ON bookings (public_number)'
            );

            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique(['show_id', 'public_number'], 'bookings_show_public_number_unique');
            $table->index('public_number');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('bookings', 'show_id')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_show_id_foreign');
            DB::statement('DROP INDEX IF EXISTS bookings_show_public_number_unique');
            DB::statement('DROP INDEX IF EXISTS bookings_public_number_index');

            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn(array_filter([
                    Schema::hasColumn('bookings', 'show_id') ? 'show_id' : null,
                    Schema::hasColumn('bookings', 'public_number') ? 'public_number' : null,
                ]));
            });

            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_show_public_number_unique');
            $table->dropIndex(['public_number']);
            $table->dropForeign(['show_id']);
            $table->dropColumn(['show_id', 'public_number']);
        });
    }
};

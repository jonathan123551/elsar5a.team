<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->unsignedBigInteger('show_id')->nullable()->after('show_time_id');
            $table->unsignedInteger('public_number')->nullable()->after('show_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('show_id')
                ->references('id')->on('shows')
                ->nullOnDelete();

            $table->unique(
                ['show_id', 'public_number'],
                'bookings_show_public_number_unique'
            );

            $table->index('public_number');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_show_public_number_unique');
            $table->dropIndex(['public_number']);
            $table->dropForeign(['show_id']);
            $table->dropColumn(['show_id', 'public_number']);
        });
    }
};

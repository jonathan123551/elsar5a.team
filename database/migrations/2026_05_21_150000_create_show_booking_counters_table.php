<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per show. `last_number` is the most-recently-allocated
 * public booking number for that show. The allocation logic in
 * BookingController@store does a single Postgres
 * `INSERT ... ON CONFLICT DO UPDATE ... RETURNING last_number`
 * statement against this table — that's atomic at the row level,
 * so two concurrent bookings for the same show serialise on the
 * `show_id` UNIQUE constraint and never collide.
 *
 * The counter is monotonic on purpose: deletes never recycle a
 * number. Printed tickets / sent WhatsApp messages referencing
 * "#5" stay accurate forever even if that booking is later
 * deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('show_booking_counters')) {
            return;
        }

        Schema::create('show_booking_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('show_booking_counters');
    }
};

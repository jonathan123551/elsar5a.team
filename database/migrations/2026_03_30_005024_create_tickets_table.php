<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guard: an earlier migration (2025_12_03_001127_create_tickets_table)
        // already creates this table with a different schema that the
        // application's Ticket model relies on. This duplicate migration is
        // a no-op when the table already exists so fresh deploys don't fail.
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('phone');

            $table->string('qr_code')->nullable();
            $table->boolean('is_sent')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

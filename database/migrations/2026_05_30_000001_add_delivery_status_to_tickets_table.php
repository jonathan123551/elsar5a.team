<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-ticket delivery lifecycle for the WhatsApp send pipeline.
     *
     * `whatsapp_sent` (bool) stays the customer-facing "this ticket has
     * been delivered" flag. `delivery_status` is the concurrency-control
     * companion that lets the queue worker CLAIM a ticket atomically:
     *
     *   pending  → not yet sent, eligible to be claimed
     *   sending  → a worker has claimed it and is mid-send (in-flight)
     *   sent     → Meta acked the send (mirrors whatsapp_sent = true)
     *   failed   → last attempt failed; eligible to be re-claimed / retried
     *
     * The compare-and-set claim (UPDATE ... WHERE delivery_status IN
     * ('pending','failed')) is what stops two concurrent webhook hits or
     * Meta webhook retries from sending the same ticket twice.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('delivery_status')->default('pending')->index()->after('whatsapp_sent');
        });

        // Backfill: anything already delivered is 'sent' so it can never be
        // re-claimed by the new pipeline.
        DB::table('tickets')->where('whatsapp_sent', true)->update(['delivery_status' => 'sent']);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
    }
};

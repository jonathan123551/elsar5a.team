<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add lightweight scanner audit fields to the tickets table.
 *
 * Why this exists
 * ---------------
 * The gate scanner is publicly reachable on purpose (real-world door
 * staff don't have admin dashboard accounts). To make burnt tickets
 * traceable after the fact — e.g. if a customer claims their ticket
 * was scanned before they arrived — we record the IP and trimmed
 * user-agent of whoever actually flipped the ticket to "used".
 *
 * Both columns are nullable so the migration is safe to run on a
 * live DB even if some tickets were scanned before this migration
 * existed. `ScannerController::check()` writes to them only when
 * `Schema::hasColumn(...)` confirms they exist, so the app degrades
 * gracefully on environments that haven't run this migration yet.
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'scanned_by_ip')) {
                $table->string('scanned_by_ip', 45)->nullable()->after('scanned_at');
            }
            if (!Schema::hasColumn('tickets', 'scanned_by_ua')) {
                $table->string('scanned_by_ua', 250)->nullable()->after('scanned_by_ip');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'scanned_by_ua')) {
                $table->dropColumn('scanned_by_ua');
            }
            if (Schema::hasColumn('tickets', 'scanned_by_ip')) {
                $table->dropColumn('scanned_by_ip');
            }
        });
    }
};

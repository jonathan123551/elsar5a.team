<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            if (!Schema::hasColumn('shows', 'ticket_template_path')) {
                $table->string('ticket_template_path')->nullable()->after('poster_path');
            }
            if (!Schema::hasColumn('shows', 'ticket_qr_x')) {
                $table->unsignedInteger('ticket_qr_x')->nullable()->after('ticket_template_path');
            }
            if (!Schema::hasColumn('shows', 'ticket_qr_y')) {
                $table->unsignedInteger('ticket_qr_y')->nullable()->after('ticket_qr_x');
            }
            if (!Schema::hasColumn('shows', 'ticket_qr_size')) {
                $table->unsignedInteger('ticket_qr_size')->nullable()->after('ticket_qr_y');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            if (Schema::hasColumn('shows', 'ticket_template_path')) {
                $table->dropColumn('ticket_template_path');
            }
            if (Schema::hasColumn('shows', 'ticket_qr_x')) {
                $table->dropColumn('ticket_qr_x');
            }
            if (Schema::hasColumn('shows', 'ticket_qr_y')) {
                $table->dropColumn('ticket_qr_y');
            }
            if (Schema::hasColumn('shows', 'ticket_qr_size')) {
                $table->dropColumn('ticket_qr_size');
            }
        });
    }
};

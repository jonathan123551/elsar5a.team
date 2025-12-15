<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            if (! Schema::hasColumn('shows', 'ticket_template_path')) {
                $table->string('ticket_template_path')->nullable();
            }

            if (! Schema::hasColumn('shows', 'ticket_qr_x')) {
                $table->integer('ticket_qr_x')->default(0);
            }

            if (! Schema::hasColumn('shows', 'ticket_qr_y')) {
                $table->integer('ticket_qr_y')->default(0);
            }

            if (! Schema::hasColumn('shows', 'ticket_qr_size')) {
                $table->integer('ticket_qr_size')->default(220);
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

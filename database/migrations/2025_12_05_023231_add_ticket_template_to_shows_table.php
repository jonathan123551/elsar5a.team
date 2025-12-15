<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('ticket_template_path')->nullable()->after('poster_path');
            $table->unsignedInteger('ticket_qr_x')->nullable()->after('ticket_template_path');
            $table->unsignedInteger('ticket_qr_y')->nullable()->after('ticket_qr_x');
            $table->unsignedInteger('ticket_qr_size')->nullable()->after('ticket_qr_y');
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_template_path',
                'ticket_qr_x',
                'ticket_qr_y',
                'ticket_qr_size',
            ]);
        });
    }
};

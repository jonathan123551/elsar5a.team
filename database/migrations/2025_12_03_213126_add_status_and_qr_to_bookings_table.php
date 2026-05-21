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
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'status')) {
                $table->string('status')->default('pending'); // pending / approved / rejected
            }
            if (!Schema::hasColumn('bookings', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable();   // مسار صورة الـ QR
            }
            if (!Schema::hasColumn('bookings', 'admin_notes')) {
                $table->text('admin_notes')->nullable();      // سبب الرفض لو حبيت
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('bookings', 'qr_code_path')) {
                $table->dropColumn('qr_code_path');
            }
            if (Schema::hasColumn('bookings', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
        });
    }

};

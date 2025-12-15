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
        $table->string('status')->default('pending'); // pending / approved / rejected
        $table->string('qr_code_path')->nullable();   // مسار صورة الـ QR
        $table->text('admin_notes')->nullable();      // سبب الرفض لو حبيت
    });
}

public function down(): void
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropColumn(['status', 'qr_code_path', 'admin_notes']);
    });
}

};

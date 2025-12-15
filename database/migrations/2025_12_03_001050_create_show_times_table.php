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
        Schema::create('show_times', function (Blueprint $table) {
            $table->id();

            // كل ميعاد مرتبط بعرض
            $table->foreignId('show_id')
                  ->constrained('shows')
                  ->cascadeOnDelete();

            // التاريخ و الوقت
            $table->date('date');        // اليوم
            $table->time('time');        // الساعة

            // تفاصيل التذاكر
            $table->unsignedInteger('ticket_price');       // سعر التذكرة
            $table->unsignedInteger('total_tickets');      // إجمالي التذاكر
            $table->unsignedInteger('available_tickets');  // التذاكر المتاحة حالياً

            // حالة الميعاد
            $table->boolean('is_sold_out')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('show_times');
    }
};

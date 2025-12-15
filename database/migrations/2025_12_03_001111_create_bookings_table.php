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
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();

        $table->foreignId('show_time_id')->constrained()->onDelete('cascade');

        $table->string('full_name');
        $table->string('phone');

        $table->unsignedInteger('tickets_count');
        $table->decimal('total_price', 10, 2);

        $table->string('payment_method')->default('manual_transfer');

        $table->enum('payment_status', [
            'pending_manual_review', 'paid', 'rejected', 'expired'
        ])->default('pending_manual_review');

        $table->string('transfer_screenshot_path')->nullable();
        $table->string('reference_code')->unique();

        $table->timestamp('paid_at')->nullable();
        $table->unsignedBigInteger('approved_by_admin_id')->nullable();
        $table->timestamp('approved_at')->nullable();

        $table->boolean('whatsapp_sent')->default(false);
        $table->timestamp('whatsapp_sent_at')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

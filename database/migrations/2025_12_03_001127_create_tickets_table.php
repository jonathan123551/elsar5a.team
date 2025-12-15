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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('booking_id')->constrained()->onDelete('cascade');
        $table->string('ticket_code')->unique();
        $table->string('qr_image_path');
        $table->boolean('is_scanned')->default(false);
        $table->timestamp('scanned_at')->nullable();
        $table->unsignedBigInteger('scanned_by_admin_id')->nullable();
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

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
        Schema::create('archive_images', function (Blueprint $table) {
            $table->id();

            // علاقة مع جدول archives
            $table->foreignId('archive_id')
                  ->constrained('archives')
                  ->cascadeOnDelete();

            // مسار الصورة
            $table->string('image_path');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_images');
    }
};

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
        Schema::create('archives', function (Blueprint $table) {
            $table->id();

            $table->string('title');                   // اسم العرض
            $table->text('description')->nullable();   // وصف العرض

            $table->string('poster_path')->nullable(); // بوستر العرض (Card image)
            $table->json('images')->nullable();        // صور من العرض (Gallery)

            $table->string('video_url')->nullable();   // لينك فيديو
            $table->integer('year')->nullable();       // سنة العرض

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};

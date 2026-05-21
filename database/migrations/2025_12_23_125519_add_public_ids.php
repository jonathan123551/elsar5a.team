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
        Schema::table('archives', function (Blueprint $table) {
            if (!Schema::hasColumn('archives', 'poster_public_id')) {
                $table->string('poster_public_id')->nullable();
            }
        });

        Schema::table('archive_images', function (Blueprint $table) {
            if (!Schema::hasColumn('archive_images', 'image_public_id')) {
                $table->string('image_public_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archives', function (Blueprint $table) {
            if (Schema::hasColumn('archives', 'poster_public_id')) {
                $table->dropColumn('poster_public_id');
            }
        });

        Schema::table('archive_images', function (Blueprint $table) {
            if (Schema::hasColumn('archive_images', 'image_public_id')) {
                $table->dropColumn('image_public_id');
            }
        });
    }
};

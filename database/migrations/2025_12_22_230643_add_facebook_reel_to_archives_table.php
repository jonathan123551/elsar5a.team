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
        if (!Schema::hasColumn('archives', 'facebook_reel')) {
            Schema::table('archives', function (Blueprint $table) {
                $table->string('facebook_reel')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('archives', 'facebook_reel')) {
            Schema::table('archives', function (Blueprint $table) {
                $table->dropColumn('facebook_reel');
            });
        }
    }
};

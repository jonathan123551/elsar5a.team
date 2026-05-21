<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('archives', function (Blueprint $table) {
            if (Schema::hasColumn('archives', 'images')) {
                $table->dropColumn('images');
            }
        });
    }

    public function down()
    {
        Schema::table('archives', function (Blueprint $table) {
            if (!Schema::hasColumn('archives', 'images')) {
                $table->text('images')->nullable();
            }
        });
    }

};

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
        Schema::create('team_applications', function (Blueprint $table) {
    $table->id();
    $table->string('full_name');
    $table->string('phone');
    $table->string('email');
    $table->integer('age');
    $table->string('education_stage');
    $table->string('school_or_college')->nullable();
    $table->string('address');
    $table->string('confession_father');
    $table->text('services')->nullable();
    $table->boolean('preparation_class');
    $table->string('department');
    $table->text('why_join');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_applications');
    }
};

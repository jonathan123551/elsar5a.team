<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('ticket_code')->unique();
                $table->string('qr_image_path')->nullable();
                $table->string('qr_code')->nullable();
                $table->boolean('is_sent')->default(false);
                $table->boolean('whatsapp_sent')->default(false);
                $table->boolean('is_scanned')->default(false);
                $table->timestamp('scanned_at')->nullable();
                $table->unsignedBigInteger('scanned_by_admin_id')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'name')) {
                $table->string('name')->nullable()->after('booking_id');
            }

            if (!Schema::hasColumn('tickets', 'phone')) {
                $table->string('phone')->nullable()->after('name');
            }

            if (!Schema::hasColumn('tickets', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('qr_image_path');
            }

            if (!Schema::hasColumn('tickets', 'is_sent')) {
                $table->boolean('is_sent')->default(false)->after('qr_code');
            }

            if (!Schema::hasColumn('tickets', 'whatsapp_sent')) {
                $table->boolean('whatsapp_sent')->default(false)->after('is_sent');
            }
        });

        $this->makeQrImagePathNullable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally no-op. This migration repairs an already-existing
        // production table; rolling it back should not drop ticket data.
    }

    private function makeQrImagePathNullable(): void
    {
        if (!Schema::hasColumn('tickets', 'qr_image_path')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN qr_image_path DROP NOT NULL');
        }
    }
};

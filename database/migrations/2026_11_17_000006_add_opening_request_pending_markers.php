<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opening_stock_request_lines', function (Blueprint $table): void {
            $table->string('pending_location_type', 20)->nullable()->after('use_system_codes');
            $table->unsignedBigInteger('pending_location_id')->nullable()->after('pending_location_type');
            $table->string('pending_marker', 20)->nullable()->after('pending_location_id');

            // Only pending lines carry the marker; resolved lines store NULL.
            // MySQL and SQLite both allow multiple NULLs in a unique index, so
            // historical resolutions never collide.
            $table->unique(
                ['product_id', 'pending_location_type', 'pending_location_id', 'pending_marker'],
                'osrl_pending_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('opening_stock_request_lines', function (Blueprint $table): void {
            $table->dropUnique('osrl_pending_unique');
            $table->dropColumn(['pending_location_type', 'pending_location_id', 'pending_marker']);
        });
    }
};

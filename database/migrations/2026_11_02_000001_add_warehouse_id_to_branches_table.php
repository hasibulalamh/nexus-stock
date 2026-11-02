<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('location_id')->constrained('warehouses')->nullOnDelete();
            $table->index('warehouse_id');
        });

        DB::statement(
            'UPDATE branches SET warehouse_id = (
                SELECT MIN(id) FROM warehouses
                WHERE warehouses.branch_id = branches.id
            )
            WHERE (
                SELECT COUNT(*) FROM warehouses
                WHERE warehouses.branch_id = branches.id
            ) = 1'
        );
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};

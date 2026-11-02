<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warehouses', 'branch_id')) {
            return;
        }

        try {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
            });
        } catch (QueryException) {
            // The column may have been created without its legacy foreign key.
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('warehouses', 'branch_id')) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('location_id')->constrained()->nullOnDelete();
        });
    }
};

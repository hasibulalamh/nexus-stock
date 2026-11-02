<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('reorder_level', 14, 3)->nullable()->change();
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->decimal('min_quantity', 14, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropColumn('min_quantity');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('reorder_level')->default(0)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('stock_movements'))->pluck('name')->all();
        Schema::table('stock_movements', function (Blueprint $table) use ($indexes) {
            if (in_array('stock_movements_reference_type_index', $indexes, true)) {
                $table->dropIndex('stock_movements_reference_type_index');
            }
            if (in_array('stock_movements_reference_id_index', $indexes, true)) {
                $table->dropIndex('stock_movements_reference_id_index');
            }
            if (! in_array('stock_movements_reference_type_reference_id_index', $indexes, true)) {
                $table->index(['reference_type', 'reference_id']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('stock_movements'))->pluck('name')->all();
        Schema::table('stock_movements', function (Blueprint $table) use ($indexes) {
            if (in_array('stock_movements_reference_type_reference_id_index', $indexes, true)) {
                $table->dropIndex('stock_movements_reference_type_reference_id_index');
            }
            if (! in_array('stock_movements_reference_type_index', $indexes, true)) {
                $table->index('reference_type');
            }
            if (! in_array('stock_movements_reference_id_index', $indexes, true)) {
                $table->index('reference_id');
            }
        });
    }
};

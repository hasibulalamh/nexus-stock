<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('serial_no')->nullable();
            $table->string('unit_code')->unique();
            $table->string('status', 20)->default('in_stock');
            $table->string('location_type', 20)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'serial_no']);
            $table->index(['product_id', 'location_type', 'location_id', 'status']);
        });

        Schema::create('stock_movement_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_unit_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['stock_movement_id', 'stock_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_units');
        Schema::dropIfExists('stock_units');
    }
};

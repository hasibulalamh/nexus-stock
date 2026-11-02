<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('location_type', 20);
            $table->unsignedBigInteger('location_id');
            $table->string('movement_type', 30);
            $table->decimal('quantity_delta', 14, 3);
            $table->decimal('balance_after', 14, 3);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['location_type', 'location_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('batch_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

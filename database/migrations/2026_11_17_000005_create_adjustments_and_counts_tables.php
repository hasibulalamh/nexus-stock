<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table): void {
            $table->id();
            $table->string('count_no', 32)->unique();
            $table->string('location_type', 20);
            $table->unsignedBigInteger('location_id');
            $table->string('status', 20)->default('open');
            $table->timestamp('snapshot_at');
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('adjustment_id')->nullable();
            $table->boolean('drift_acknowledged')->default(false);
            $table->timestamps();

            $table->index(['location_type', 'location_id', 'status'], 'stock_counts_loc_status_idx');
            $table->index('adjustment_id', 'stock_counts_adjustment_idx');
        });

        Schema::create('stock_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('system_qty', 14, 3);
            $table->decimal('counted_qty', 14, 3)->nullable();
            $table->decimal('variance', 14, 3)->nullable();
            $table->decimal('drift_qty', 14, 3)->nullable();
            $table->timestamps();

            $table->unique(['stock_count_id', 'product_id'], 'stock_count_lines_uq');
        });

        Schema::create('stock_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->string('adj_no', 32)->unique();
            $table->string('location_type', 20);
            $table->unsignedBigInteger('location_id');
            $table->string('reason', 30);
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('stock_count_id')->nullable()->constrained('stock_counts')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'location_type', 'location_id'], 'stock_adj_status_loc_idx');
            $table->index('stock_count_id', 'stock_adj_count_idx');
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('delta', 14, 3);
            $table->json('stock_unit_ids')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->timestamps();

            $table->unique(['stock_adjustment_id', 'product_id'], 'stock_adj_lines_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};

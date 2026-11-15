<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('transfer_no', 32)->unique();
            $table->string('type', 20)->default('transfer');
            $table->string('from_type', 20);
            $table->unsignedBigInteger('from_id');
            $table->string('to_type', 20);
            $table->unsignedBigInteger('to_id');
            $table->string('status', 20)->default('draft');
            $table->string('reason', 40)->nullable();
            $table->foreignId('stock_alert_id')->nullable()->constrained('stock_alerts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'from_type', 'from_id'], 'transfers_status_from_idx');
            $table->index(['status', 'to_type', 'to_id'], 'transfers_status_to_idx');
            $table->index(['type', 'status'], 'transfers_type_status_idx');
        });

        Schema::create('transfer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('requested_qty', 14, 3)->default(0);
            $table->decimal('sent_qty', 14, 3)->default(0);
            $table->decimal('received_qty', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['transfer_id', 'product_id'], 'transfer_items_transfer_product_uq');
        });

        Schema::create('transfer_item_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_unit_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['transfer_item_id', 'stock_unit_id'], 'transfer_item_units_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_item_units');
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
    }
};

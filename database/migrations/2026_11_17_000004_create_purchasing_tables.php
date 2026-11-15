<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'suppliers_status_idx');
            $table->index('name', 'suppliers_name_idx');
        });

        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('po_no', 32)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status'], 'po_warehouse_status_idx');
            $table->index(['supplier_id', 'status'], 'po_supplier_status_idx');
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('ordered_qty', 14, 3);
            $table->decimal('received_qty', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['purchase_order_id', 'product_id'], 'po_lines_po_product_uq');
        });

        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('grn_no', 32)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->timestamp('posted_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status'], 'grn_warehouse_status_idx');
            $table->index('supplier_id', 'grn_supplier_idx');
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('received_qty', 14, 3);
            $table->decimal('accepted_qty', 14, 3);
            $table->decimal('damaged_qty', 14, 3)->default(0);
            $table->decimal('short_qty', 14, 3)->default(0);
            $table->decimal('returned_qty', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->timestamps();

            $table->index('goods_receipt_id', 'grn_lines_grn_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_stock_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_no')->unique();
            $table->string('location_type', 20);
            $table->unsignedBigInteger('location_id');
            $table->date('occurred_at');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('resubmitted_from_id')->nullable()->constrained('opening_stock_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'location_type', 'location_id']);
        });

        Schema::create('opening_stock_request_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('opening_stock_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->json('serial_numbers')->nullable();
            $table->boolean('use_system_codes')->default(false);
            $table->timestamps();

            $table->index(['request_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_stock_request_lines');
        Schema::dropIfExists('opening_stock_requests');
    }
};

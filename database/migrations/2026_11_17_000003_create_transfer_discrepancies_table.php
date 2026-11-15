<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_discrepancies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('qty', 14, 3);
            $table->text('reason')->nullable();
            $table->json('stock_unit_ids')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('resolution', 20)->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type'], 'transfer_disc_status_type_idx');
            $table->index('transfer_item_id', 'transfer_disc_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_discrepancies');
    }
};

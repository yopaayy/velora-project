<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->string('type', 20)->default('main');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'code']);
            $table->index('branch_id');
        });

        // 2. product_warehouse
        Schema::create('product_warehouse', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->decimal('available_quantity', 15, 4)->storedAs('quantity - reserved_quantity');
            $table->unsignedBigInteger('cost_price_avg')->default(0);
            $table->timestamp('last_restock_at')->nullable();
            $table->timestamps();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->unique(['product_id', 'product_variant_id', 'warehouse_id'], 'pw_unique');
            $table->index(['business_id', 'warehouse_id']);
        });

        // 3. batches
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('batch_number', 50);
            $table->decimal('quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->date('manufactured_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->index(['business_id', 'product_id', 'expired_at']);
            $table->index(['business_id', 'expired_at', 'status']);
        });

        // 4. stock_movements (append-only ledger)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->uuid('batch_id')->nullable();
            $table->string('reference_type', 100);
            $table->uuid('reference_id');
            $table->string('movement_type', 20);
            $table->decimal('quantity', 15, 4);
            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('base_quantity', 15, 4);
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->decimal('stock_before', 15, 4);
            $table->decimal('stock_after', 15, 4);
            $table->text('note')->nullable();
            $table->uuid('performed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['warehouse_id', 'created_at']);
        });

        // 5. stock_transfers
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('transfer_number', 50)->unique();
            $table->foreignUuid('from_warehouse_id')->constrained('warehouses');
            $table->foreignUuid('to_warehouse_id')->constrained('warehouses');
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();
            $table->foreignUuid('transferred_by')->constrained('users');
            $table->uuid('received_by')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });

        // 6. stock_transfer_items
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->decimal('quantity_sent', 15, 4);
            $table->decimal('quantity_received', 15, 4)->default(0);
            $table->foreignUuid('unit_id')->constrained('units');
            $table->decimal('base_quantity_sent', 15, 4);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });

        // 7. stock_opnames
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('opname_number', 50)->unique();
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();
            $table->foreignUuid('started_by')->constrained('users');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // 8. stock_opname_items
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->decimal('system_quantity', 15, 4);
            $table->decimal('actual_quantity', 15, 4);
            $table->decimal('difference', 15, 4)->storedAs('actual_quantity - system_quantity');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });

        // 9. stock_adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('adjustment_number', 50)->unique();
            $table->string('reason', 50);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignUuid('adjusted_by')->constrained('users');
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // 10. stock_adjustment_items
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->foreignUuid('unit_id')->constrained('units');
            $table->decimal('base_quantity', 15, 4);
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('product_warehouse');
        Schema::dropIfExists('warehouses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════ SALES MODULE ═══════════════

        // 1. cashier_shifts
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('shift_number', 50)->unique();
            $table->unsignedBigInteger('opening_amount')->default(0);
            $table->unsignedBigInteger('closing_amount')->nullable();
            $table->unsignedBigInteger('expected_amount')->nullable();
            $table->bigInteger('difference')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'branch_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // 2. CRM: membership_tiers (needed before customers)
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 50);
            $table->unsignedBigInteger('min_spent')->default(0);
            $table->unsignedInteger('min_transactions')->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('points_multiplier', 5, 2)->default(1);
            $table->json('benefits')->nullable();
            $table->string('color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. CRM: customers
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->uuid('membership_tier_id')->nullable();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('member_code', 30)->nullable();
            $table->unsignedInteger('points_balance')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->unsignedInteger('total_transactions')->default(0);
            $table->timestamp('last_transaction_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('membership_tier_id')->references('id')->on('membership_tiers')->nullOnDelete();
            $table->index(['business_id', 'phone']);
            $table->index(['business_id', 'member_code']);
            $table->index(['business_id', 'email']);
        });

        // 4. transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->uuid('cashier_shift_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('transaction_number', 50)->unique();
            $table->date('transaction_date');
            $table->string('transaction_type', 20)->default('sale');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->uuid('discount_id')->nullable();
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->bigInteger('rounding_amount')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->string('payment_status', 20)->default('paid');
            $table->string('status', 20)->default('completed');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->uuid('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('cashier_shift_id')->references('id')->on('cashier_shifts')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('discount_id')->references('id')->on('discounts')->nullOnDelete();
            $table->index(['business_id', 'branch_id', 'transaction_date']);
            $table->index('customer_id');
            $table->index('cashier_shift_id');
        });

        // 5. transaction_items
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->string('product_name', 200);
            $table->string('sku', 50)->nullable();
            $table->decimal('quantity', 15, 4);
            $table->foreignUuid('unit_id')->constrained('units');
            $table->decimal('base_quantity', 15, 4);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('cost_price');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
        });

        // 6. transaction_payments
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('payment_method_id')->constrained('payment_methods');
            $table->unsignedBigInteger('amount');
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamp('created_at')->useCurrent();
        });

        // 7. refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('refund_number', 50)->unique();
            $table->string('refund_type', 20);
            $table->unsignedBigInteger('total_amount');
            $table->string('refund_method', 30);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignUuid('refunded_by')->constrained('users');
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // 8. refund_items
        Schema::create('refund_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('refund_id')->constrained('refunds')->cascadeOnDelete();
            $table->foreignUuid('transaction_item_id')->constrained('transaction_items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->unsignedBigInteger('amount');
            $table->boolean('return_to_stock')->default(true);
            $table->string('condition', 20)->default('good');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ═══════════════ PURCHASING MODULE ═══════════════

        // 9. suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 30)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->integer('payment_terms')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 10. purchases
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('purchase_number', 50)->unique();
            $table->date('purchase_date');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('status', 20)->default('draft');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 11. purchase_items
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->decimal('quantity_ordered', 15, 4);
            $table->decimal('quantity_received', 15, 4)->default(0);
            $table->foreignUuid('unit_id')->constrained('units');
            $table->decimal('base_quantity_ordered', 15, 4);
            $table->decimal('base_quantity_received', 15, 4)->default(0);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('batch_number', 50)->nullable();
            $table->date('expired_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        // 12. supplier_debts
        Schema::create('supplier_debts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount')->storedAs('total_amount - paid_amount');
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            $table->timestamps();
        });

        // 13. supplier_debt_payments
        Schema::create('supplier_debt_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_debt_id')->constrained('supplier_debts')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignUuid('payment_method_id')->constrained('payment_methods');
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignUuid('paid_by')->constrained('users');
            $table->timestamp('paid_at');
            $table->timestamp('created_at')->useCurrent();
        });

        // ═══════════════ CRM MODULE (remaining) ═══════════════

        // 14. loyalty_points (append-only ledger)
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->uuid('transaction_id')->nullable();
            $table->string('type', 20);
            $table->integer('points');
            $table->unsignedInteger('balance_after');
            $table->string('description', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->index(['customer_id', 'created_at']);
            $table->index(['business_id', 'expires_at']);
        });

        // 15. vouchers
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('type', 20);
            $table->decimal('value', 15, 2);
            $table->unsignedBigInteger('min_purchase')->default(0);
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('applicable_tiers')->nullable();
            $table->json('applicable_products')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });

        // 16. voucher_usages
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->uuid('customer_id')->nullable();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });

        // 17. customer_debts
        Schema::create('customer_debts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount')->storedAs('total_amount - paid_amount');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('unpaid');
            $table->timestamps();
        });

        // 18. customer_debt_payments
        Schema::create('customer_debt_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_debt_id')->constrained('customer_debts')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignUuid('payment_method_id')->constrained('payment_methods');
            $table->string('reference', 100)->nullable();
            $table->foreignUuid('received_by')->constrained('users');
            $table->timestamp('paid_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_payments');
        Schema::dropIfExists('customer_debts');
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('supplier_debt_payments');
        Schema::dropIfExists('supplier_debts');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('transaction_payments');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('membership_tiers');
        Schema::dropIfExists('cashier_shifts');
    }
};

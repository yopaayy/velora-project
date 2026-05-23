<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════ FINANCE MODULE ═══════════════

        // 1. expense_categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('expense_categories')->nullOnDelete();
        });

        // 2. expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->string('expense_number', 50)->unique();
            $table->string('description', 500);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->date('expense_date');
            $table->uuid('payment_method_id')->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->string('status', 20)->default('approved');
            $table->uuid('approved_by')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // 3. income
        Schema::create('income', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('income_number', 50)->unique();
            $table->string('source', 50);
            $table->string('description', 500);
            $table->unsignedBigInteger('amount');
            $table->date('income_date');
            $table->string('reference_type', 100)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->uuid('payment_method_id')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();

            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
        });

        // 4. cash_flows (auto-populated via events)
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('category', 30);
            $table->unsignedBigInteger('amount');
            $table->bigInteger('balance_after');
            $table->string('reference_type', 100)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->date('flow_date');
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'branch_id', 'flow_date']);
            $table->index(['business_id', 'type', 'flow_date']);
        });

        // 5. chart_of_accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('type', 20);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->bigInteger('balance')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });

        // 6. journals
        Schema::create('journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->uuid('branch_id')->nullable();
            $table->string('journal_number', 50)->unique();
            $table->date('journal_date');
            $table->string('description', 500);
            $table->string('reference_type', 100)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->unsignedBigInteger('total_debit')->default(0);
            $table->unsignedBigInteger('total_credit')->default(0);
            $table->boolean('is_auto')->default(false);
            $table->string('status', 20)->default('posted');
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });

        // 7. journal_entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->string('account_code', 20);
            $table->string('account_name', 100);
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ═══════════════ SETTINGS MODULE ═══════════════

        // 8. business_settings
        Schema::create('business_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('group', 30);
            $table->string('key', 50);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'group', 'key']);
        });

        // 9. number_sequences
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('prefix', 10);
            $table->string('separator', 5)->default('-');
            $table->boolean('include_date')->default(true);
            $table->unsignedInteger('current_number')->default(0);
            $table->integer('padding')->default(5);
            $table->string('reset_period', 10)->default('daily');
            $table->date('last_reset_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'type']);
        });

        // ═══════════════ ADDENDUM: Multi-Currency ═══════════════

        // 10. currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 5)->unique();
            $table->string('name', 50);
            $table->string('symbol', 5);
            $table->tinyInteger('decimal_places')->default(0);
            $table->string('thousand_separator', 1)->default('.');
            $table->string('decimal_separator', 1)->default(',');
            $table->string('symbol_position', 10)->default('before');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 11. exchange_rates
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('from_currency', 5);
            $table->string('to_currency', 5);
            $table->decimal('rate', 18, 6);
            $table->string('source', 30)->default('manual');
            $table->date('effective_date');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['from_currency', 'to_currency', 'effective_date'], 'exchange_rate_unique');
        });

        // ═══════════════ ADDENDUM: Midtrans ═══════════════

        // 12. midtrans_transactions
        Schema::create('midtrans_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('payable_type', 100);
            $table->uuid('payable_id');
            $table->string('order_id', 50)->unique();
            $table->string('snap_token', 100)->nullable();
            $table->string('snap_url', 500)->nullable();
            $table->string('payment_type', 30)->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->string('currency', 5)->default('IDR');
            $table->string('va_number', 50)->nullable();
            $table->string('bank', 20)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('transaction_status', 30)->default('pending');
            $table->string('fraud_status', 20)->nullable();
            $table->string('status_code', 5)->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['business_id', 'transaction_status']);
        });

        // 13. manual_payments
        Schema::create('manual_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_payment_id')->constrained('subscription_payments')->cascadeOnDelete();
            $table->string('bank_name', 50);
            $table->string('account_name', 100);
            $table->string('account_number', 50);
            $table->unsignedBigInteger('transfer_amount');
            $table->date('transfer_date');
            $table->string('proof_image_url', 500);
            $table->string('status', 20)->default('pending');
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        // 14. Add billing_mode to subscriptions & subscription_payments
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_mode', 20)->default('auto')->after('auto_renew');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('billing_mode', 20)->default('auto')->after('status');
        });

        // ═══════════════ AI MODULE ═══════════════

        // 15. ai_queries
        Schema::create('ai_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('query_type', 30);
            $table->text('prompt');
            $table->longText('response')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedBigInteger('cost')->default(0);
            $table->string('model', 50)->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamps();

            $table->index(['business_id', 'query_type', 'created_at']);
        });

        // 16. forecasts
        Schema::create('forecasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('forecast_type', 30);
            $table->uuid('target_id')->nullable();
            $table->string('target_type', 100)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->json('predictions')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'forecast_type', 'period_start']);
        });

        // 17. anomalies
        Schema::create('anomalies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('anomaly_type', 30);
            $table->string('severity', 20)->default('medium');
            $table->string('module', 30);
            $table->string('description', 500);
            $table->json('data')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'is_resolved', 'created_at']);
        });

        // 18. ai_usage_logs
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('total_queries')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('anomalies');
        Schema::dropIfExists('forecasts');
        Schema::dropIfExists('ai_queries');

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn('billing_mode');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_mode');
        });

        Schema::dropIfExists('manual_payments');
        Schema::dropIfExists('midtrans_transactions');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('cash_flows');
        Schema::dropIfExists('income');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};

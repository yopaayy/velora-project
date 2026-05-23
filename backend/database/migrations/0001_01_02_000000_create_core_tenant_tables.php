<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. businesses
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('legal_name', 200)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('business_type', 30);
            $table->string('industry', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country', 5)->default('ID');
            $table->string('currency', 5)->default('IDR');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('status', 20)->default('active');
            $table->timestamp('locked_at')->nullable();
            $table->string('locked_reason', 100)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // 2. branches
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->string('type', 20)->default('store');
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_main')->default(0);
            $table->boolean('is_active')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->unique(['business_id', 'code']);
        });

        // 3. roles
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 50);
            $table->string('display_name', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(0);
            $table->unsignedInteger('level')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
        });

        // 4. permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module', 30);
            $table->string('name', 80)->unique();
            $table->string('display_name', 150)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // 5. role_permission
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // 6. business_user
        Schema::create('business_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->boolean('is_owner')->default(0);
            $table->timestamp('joined_at');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });

        // 7. branch_user
        Schema::create('branch_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_default')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
        });

        // 8. subscription_plans
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_monthly')->default(0);
            $table->unsignedBigInteger('price_quarterly')->default(0);
            $table->unsignedBigInteger('price_biannual')->default(0);
            $table->unsignedBigInteger('price_annual')->default(0);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('grace_period_days')->default(3);
            $table->boolean('is_active')->default(1);
            $table->boolean('is_featured')->default(0);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 9. feature_limits
        Schema::create('feature_limits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('feature_key', 50);
            $table->string('feature_value', 50);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });

        // 10. subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('billing_cycle', 20);
            $table->string('status', 20);
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->boolean('auto_renew')->default(1);
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index('ends_at');
        });

        // 11. subscription_payments
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_gateway', 30)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->string('status', 20);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 12. subscription_logs
        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('action', 30);
            $table->foreignUuid('from_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->foreignUuid('to_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 13. activity_logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('module', 30)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // 14. audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type', 150);
            $table->uuid('auditable_id');
            $table->string('event', 20);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('subscription_logs');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('feature_limits');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('business_user');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};

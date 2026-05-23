<?php

namespace Database\Seeders;

use App\Modules\Subscription\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for small businesses just getting started.',
                'price_monthly' => 0,
                'price_quarterly' => 0,
                'price_biannual' => 0,
                'price_annual' => 0,
                'trial_days' => 0,
                'grace_period_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'limits' => [
                    'max_branches' => 1,
                    'max_users' => 1,
                    'max_products' => 50,
                    'max_transactions_per_month' => 100,
                    'support_level' => 'community',
                ]
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For growing businesses needing more features.',
                'price_monthly' => 149000,
                'price_quarterly' => 420000,
                'price_biannual' => 800000,
                'price_annual' => 1500000,
                'trial_days' => 14,
                'grace_period_days' => 7,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'limits' => [
                    'max_branches' => 3,
                    'max_users' => 5,
                    'max_products' => 1000,
                    'max_transactions_per_month' => -1, // Unlimited
                    'support_level' => 'standard',
                ]
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Advanced POS features with AI insights.',
                'price_monthly' => 299000,
                'price_quarterly' => 850000,
                'price_biannual' => 1600000,
                'price_annual' => 3000000,
                'trial_days' => 14,
                'grace_period_days' => 7,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'limits' => [
                    'max_branches' => 10,
                    'max_users' => 20,
                    'max_products' => -1, // Unlimited
                    'max_transactions_per_month' => -1,
                    'support_level' => 'priority',
                    'ai_queries_per_month' => 1000,
                ]
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solutions for large scale operations.',
                'price_monthly' => 799000,
                'price_quarterly' => 2200000,
                'price_biannual' => 4200000,
                'price_annual' => 8000000,
                'trial_days' => 30,
                'grace_period_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'limits' => [
                    'max_branches' => -1, // Unlimited
                    'max_users' => -1, // Unlimited
                    'max_products' => -1,
                    'max_transactions_per_month' => -1,
                    'support_level' => '24/7 dedicated',
                    'ai_queries_per_month' => -1, // Unlimited
                ]
            ],
        ];

        foreach ($plans as $planData) {
            $limits = $planData['limits'];
            unset($planData['limits']);

            $plan = SubscriptionPlan::firstOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            // Create limits
            foreach ($limits as $key => $value) {
                $plan->featureLimits()->firstOrCreate([
                    'feature_key' => $key
                ], [
                    'feature_value' => (string) $value
                ]);
            }
        }
    }
}

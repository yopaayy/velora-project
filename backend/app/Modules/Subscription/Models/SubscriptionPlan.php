<?php

namespace App\Modules\Subscription\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasUuid;

    protected $fillable = [
        'name', 'slug', 'description',
        'price_monthly', 'price_quarterly', 'price_biannual', 'price_annual',
        'trial_days', 'grace_period_days', 'is_active', 'is_featured',
        'sort_order', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function featureLimits() { return $this->hasMany(FeatureLimit::class, 'plan_id'); }
    public function subscriptions() { return $this->hasMany(Subscription::class, 'plan_id'); }
}

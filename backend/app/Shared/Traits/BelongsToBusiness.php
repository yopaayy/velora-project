<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            if ($businessId = static::resolveCurrentBusinessId()) {
                $builder->where($builder->getModel()->getTable() . '.business_id', $businessId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->business_id)) {
                $model->business_id = static::resolveCurrentBusinessId();
            }
        });
    }

    protected static function resolveCurrentBusinessId(): ?string
    {
        return request()?->header('X-Business-Id') ?? auth()->user()?->current_business_id ?? null;
    }

    public function business(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Business::class, 'business_id');
    }
}

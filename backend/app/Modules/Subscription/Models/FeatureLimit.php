<?php

namespace App\Modules\Subscription\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FeatureLimit extends Model
{
    use HasUuid;

    protected $fillable = ['plan_id', 'feature_key', 'feature_value'];

    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'plan_id'); }
}

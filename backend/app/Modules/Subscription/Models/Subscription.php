<?php

namespace App\Modules\Subscription\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'plan_id', 'billing_cycle', 'status',
        'trial_starts_at', 'trial_ends_at', 'starts_at', 'ends_at',
        'grace_ends_at', 'cancelled_at', 'cancel_reason',
        'auto_renew', 'billing_mode',
    ];

    protected function casts(): array
    {
        return [
            'trial_starts_at' => 'datetime', 'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime', 'ends_at' => 'datetime',
            'grace_ends_at' => 'datetime', 'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'plan_id'); }
    public function payments() { return $this->hasMany(SubscriptionPayment::class); }
    public function logs() { return $this->hasMany(SubscriptionLog::class); }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isOnTrial(): bool { return $this->status === 'trial' && now()->lt($this->trial_ends_at); }
    public function isInGracePeriod(): bool { return $this->status === 'grace' && now()->lt($this->grace_ends_at); }
}

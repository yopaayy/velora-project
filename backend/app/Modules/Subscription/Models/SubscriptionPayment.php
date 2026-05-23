<?php

namespace App\Modules\Subscription\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'subscription_id', 'invoice_number',
        'amount', 'discount_amount', 'tax_amount', 'total_amount',
        'payment_method', 'payment_gateway', 'gateway_transaction_id',
        'status', 'billing_mode', 'paid_at', 'due_at', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'due_at' => 'datetime', 'metadata' => 'array'];
    }

    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function manualPayment() { return $this->hasOne(ManualPayment::class); }
}

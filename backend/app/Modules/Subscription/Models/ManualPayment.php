<?php
namespace App\Modules\Subscription\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ManualPayment extends Model
{
    use HasUuid;
    protected $fillable = [
        'subscription_payment_id','bank_name','account_name','account_number',
        'transfer_amount','transfer_date','proof_image_url','status',
        'verified_by','verified_at','rejected_reason',
    ];
    protected function casts(): array { return ['transfer_date' => 'date', 'verified_at' => 'datetime']; }
    public function subscriptionPayment() { return $this->belongsTo(SubscriptionPayment::class); }
    public function verifier() { return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'verified_by'); }
}

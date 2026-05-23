<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'transaction_id', 'refund_number', 'refund_type',
        'total_amount', 'refund_method', 'reason', 'status',
        'refunded_by', 'approved_by',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items()
    {
        return $this->hasMany(RefundItem::class);
    }

    public function refundedBy()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'refunded_by');
    }
}

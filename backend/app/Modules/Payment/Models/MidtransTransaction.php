<?php
namespace App\Modules\Payment\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class MidtransTransaction extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = [
        'business_id','payable_type','payable_id','order_id','snap_token','snap_url',
        'payment_type','gross_amount','currency','va_number','bank','transaction_id',
        'transaction_status','fraud_status','status_code','settlement_time','expiry_time','metadata',
    ];
    protected function casts(): array {
        return ['settlement_time'=>'datetime','expiry_time'=>'datetime','metadata'=>'array'];
    }
    public function payable() { return $this->morphTo(); }
}

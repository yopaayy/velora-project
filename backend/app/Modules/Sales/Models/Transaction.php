<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\BelongsToBranch;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuid, BelongsToBusiness, BelongsToBranch, Filterable;

    protected $fillable = [
        'business_id', 'branch_id', 'warehouse_id', 'cashier_shift_id',
        'customer_id', 'user_id', 'transaction_number', 'transaction_date',
        'transaction_type', 'subtotal', 'discount_amount', 'discount_id',
        'tax_amount', 'rounding_amount', 'grand_total', 'paid_amount',
        'change_amount', 'payment_status', 'status', 'note', 'metadata',
        'voided_at', 'voided_by', 'void_reason',
    ];

    protected $searchable = ['transaction_number'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'metadata' => 'array',
            'voided_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments()
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'user_id');
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }
}

<?php
namespace App\Modules\Purchasing\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\BelongsToBranch;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasUuid, BelongsToBusiness, BelongsToBranch, Filterable;

    protected $fillable = [
        'business_id', 'branch_id', 'warehouse_id', 'supplier_id',
        'user_id', 'purchase_number', 'purchase_date', 'expected_date',
        'received_date', 'subtotal', 'discount_amount', 'tax_amount',
        'shipping_cost', 'grand_total', 'paid_amount', 'payment_status',
        'status', 'note',
    ];

    protected $searchable = ['purchase_number'];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'expected_date' => 'date',
            'received_date' => 'date',
        ];
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

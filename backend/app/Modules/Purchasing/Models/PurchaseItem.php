<?php
namespace App\Modules\Purchasing\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'purchase_id', 'product_id', 'product_variant_id', 'quantity_ordered',
        'quantity_received', 'unit_id', 'base_quantity_ordered',
        'base_quantity_received', 'unit_price', 'discount_amount',
        'tax_amount', 'total', 'batch_number', 'expired_at', 'note', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Modules\POS\Models\Product::class);
    }
}

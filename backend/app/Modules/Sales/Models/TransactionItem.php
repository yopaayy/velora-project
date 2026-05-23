<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id', 'product_id', 'product_variant_id', 'batch_id',
        'product_name', 'sku', 'quantity', 'unit_id', 'base_quantity',
        'unit_price', 'cost_price', 'subtotal', 'discount_amount',
        'tax_amount', 'total', 'note', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Modules\POS\Models\Product::class);
    }
}

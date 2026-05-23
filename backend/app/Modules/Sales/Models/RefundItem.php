<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'refund_id', 'transaction_item_id', 'quantity', 'amount',
        'return_to_stock', 'condition', 'note', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'return_to_stock' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}

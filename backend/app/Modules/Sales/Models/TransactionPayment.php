<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TransactionPayment extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id', 'payment_method_id', 'amount',
        'reference', 'status', 'created_at',
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

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}

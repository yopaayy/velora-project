<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasUuid, BelongsToBusiness, BelongsToBranch;

    protected $fillable = [
        'business_id', 'branch_id', 'user_id', 'shift_number',
        'opening_amount', 'closing_amount', 'expected_amount',
        'difference', 'status', 'opened_at', 'closed_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

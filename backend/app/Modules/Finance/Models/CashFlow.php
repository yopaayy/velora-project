<?php
namespace App\Modules\Finance\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\BelongsToBranch;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    use HasUuid, BelongsToBusiness, BelongsToBranch, Filterable;

    public $timestamps = false;

    protected $fillable = [
        'business_id', 'branch_id', 'type', 'category', 'amount',
        'balance_after', 'reference_type', 'reference_id',
        'description', 'flow_date', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'flow_date' => 'date',
            'created_at' => 'datetime',
        ];
    }
}

<?php
namespace App\Modules\Sales\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'code', 'type',
        'is_active', 'is_default', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'settings' => 'array',
        ];
    }
}

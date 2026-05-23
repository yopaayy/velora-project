<?php
namespace App\Modules\CRM\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'code', 'name', 'type', 'value',
        'min_purchase', 'max_discount', 'usage_limit',
        'usage_per_customer', 'used_count', 'starts_at',
        'ends_at', 'is_active', 'applicable_tiers', 'applicable_products',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'applicable_tiers' => 'array',
            'applicable_products' => 'array',
        ];
    }
}

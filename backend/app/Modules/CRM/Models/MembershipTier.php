<?php
namespace App\Modules\CRM\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class MembershipTier extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'slug', 'min_spent', 'min_transactions',
        'discount_percentage', 'points_multiplier', 'benefits',
        'color', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'points_multiplier' => 'decimal:2',
            'benefits' => 'array',
            'is_active' => 'boolean',
        ];
    }
}

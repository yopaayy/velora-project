<?php
namespace App\Modules\CRM\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuid, BelongsToBusiness, Filterable, SoftDeletes;

    protected $fillable = [
        'business_id', 'membership_tier_id', 'name', 'email', 'phone',
        'gender', 'birth_date', 'address', 'city', 'member_code',
        'points_balance', 'total_spent', 'total_transactions',
        'last_transaction_at', 'notes', 'is_active',
    ];

    protected $searchable = ['name', 'email', 'phone', 'member_code'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_transaction_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function tier()
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }

    public function transactions()
    {
        return $this->hasMany(\App\Modules\Sales\Models\Transaction::class);
    }
}

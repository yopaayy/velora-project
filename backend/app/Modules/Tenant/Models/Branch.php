<?php

namespace App\Modules\Tenant\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasUuid, BelongsToBusiness, Filterable, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'code', 'type', 'phone', 'email',
        'address', 'city', 'province', 'postal_code',
        'latitude', 'longitude', 'is_main', 'is_active', 'settings',
    ];

    protected $searchable = ['name', 'code', 'city'];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(\App\Modules\Auth\Models\User::class, 'branch_user')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function warehouses()
    {
        return $this->hasMany(\App\Modules\Inventory\Models\Warehouse::class);
    }
}

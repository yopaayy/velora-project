<?php

namespace App\Modules\Tenant\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasUuid, Filterable, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'legal_name', 'tax_id',
        'business_type', 'industry', 'phone', 'email', 'website',
        'logo_url', 'address', 'city', 'province', 'postal_code',
        'country', 'currency', 'timezone', 'status',
        'locked_at', 'locked_reason', 'settings',
    ];

    protected $searchable = ['name', 'slug', 'email', 'phone'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'locked_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function owner()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'owner_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Modules\Auth\Models\User::class, 'business_user')
            ->withPivot('role_id', 'is_owner', 'status', 'joined_at')
            ->withTimestamps();
    }

    public function roles()
    {
        return $this->hasMany(\App\Modules\Auth\Models\Role::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Modules\Subscription\Models\Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(\App\Modules\Subscription\Models\Subscription::class)
            ->where('status', 'active')
            ->latest();
    }

    public function settings()
    {
        return $this->hasMany(\App\Modules\Setting\Models\BusinessSetting::class);
    }
}

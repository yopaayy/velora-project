<?php

namespace App\Modules\Auth\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuid, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'avatar_url',
        'email_verified_at', 'is_platform_admin', 'last_login_at',
        'last_login_ip', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_platform_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // ─── Relationships ───

    public function businesses()
    {
        return $this->belongsToMany(\App\Modules\Tenant\Models\Business::class, 'business_user')
            ->withPivot('role_id', 'is_owner', 'status', 'joined_at')
            ->withTimestamps();
    }

    public function ownedBusinesses()
    {
        return $this->hasMany(\App\Modules\Tenant\Models\Business::class, 'owner_id');
    }

    public function branches()
    {
        return $this->belongsToMany(\App\Modules\Tenant\Models\Branch::class, 'branch_user')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(\App\Modules\Audit\Models\ActivityLog::class, 'user_id');
    }
}

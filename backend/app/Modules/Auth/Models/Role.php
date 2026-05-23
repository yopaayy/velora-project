<?php

namespace App\Modules\Auth\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasUuid;

    protected $fillable = [
        'business_id', 'name', 'slug', 'display_name', 'description',
        'is_system', 'level',
    ];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function business()
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Business::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            \App\Modules\Tenant\Models\BusinessUser::class,
            'role_id', 'id', 'id', 'user_id'
        );
    }
}

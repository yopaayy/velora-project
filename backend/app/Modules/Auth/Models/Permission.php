<?php

namespace App\Modules\Auth\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasUuid;

    protected $fillable = ['module', 'name', 'display_name', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}

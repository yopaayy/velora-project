<?php

namespace App\Modules\Tenant\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class BusinessUser extends Model
{
    use HasUuid;

    protected $table = 'business_user';

    protected $fillable = [
        'business_id', 'user_id', 'role_id', 'is_owner', 'joined_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function business() { return $this->belongsTo(Business::class); }
    public function user() { return $this->belongsTo(\App\Modules\Auth\Models\User::class); }
    public function role() { return $this->belongsTo(\App\Modules\Auth\Models\Role::class); }
}

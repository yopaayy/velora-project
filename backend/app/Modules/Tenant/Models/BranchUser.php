<?php

namespace App\Modules\Tenant\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class BranchUser extends Model
{
    use HasUuid;

    protected $table = 'branch_user';

    protected $fillable = ['branch_id', 'user_id', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(\App\Modules\Auth\Models\User::class); }
}

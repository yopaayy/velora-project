<?php

namespace App\Shared\Traits;

trait BelongsToBranch
{
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Branch::class, 'branch_id');
    }
}

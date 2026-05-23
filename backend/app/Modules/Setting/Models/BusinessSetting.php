<?php
namespace App\Modules\Setting\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'group', 'key', 'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}

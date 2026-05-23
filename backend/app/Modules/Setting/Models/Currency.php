<?php
namespace App\Modules\Setting\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasUuid;

    protected $fillable = [
        'code', 'name', 'symbol', 'decimal_places',
        'thousand_separator', 'decimal_separator',
        'symbol_position', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

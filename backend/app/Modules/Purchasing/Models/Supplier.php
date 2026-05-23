<?php
namespace App\Modules\Purchasing\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasUuid, BelongsToBusiness, Filterable, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'code', 'contact_person', 'phone', 'email',
        'address', 'city', 'tax_id', 'payment_terms', 'is_active', 'notes',
    ];

    protected $searchable = ['name', 'code', 'contact_person', 'email', 'phone'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

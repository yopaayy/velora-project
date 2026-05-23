<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasUuid, BelongsToBusiness, Filterable, SoftDeletes;

    protected $fillable = [
        'business_id', 'parent_id', 'name', 'slug', 'color', 'icon',
        'is_active', 'sort_order',
    ];
    protected $searchable = ['name', 'slug'];
    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function products() { return $this->hasMany(Product::class, 'category_id'); }
}

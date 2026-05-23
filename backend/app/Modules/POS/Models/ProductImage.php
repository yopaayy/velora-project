<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasUuid;
    protected $fillable = ['product_id','path','is_primary','sort_order'];
    protected function casts(): array { return ['is_primary'=>'boolean']; }
    public function product() { return $this->belongsTo(Product::class); }
}

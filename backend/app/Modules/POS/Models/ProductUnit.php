<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasUuid;
    protected $fillable = ['product_id','unit_id','conversion_factor','selling_price','cost_price','is_default'];
    protected function casts(): array { return ['is_default'=>'boolean']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}

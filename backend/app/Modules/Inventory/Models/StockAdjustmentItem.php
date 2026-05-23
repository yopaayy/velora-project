<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    use HasUuid;
    public $timestamps = false;
    protected $fillable = ['stock_adjustment_id','product_id','product_variant_id','quantity','unit_id','base_quantity','cost_price','note','created_at'];
    protected function casts(): array { return ['created_at'=>'datetime']; }
    public function adjustment() { return $this->belongsTo(StockAdjustment::class,'stock_adjustment_id'); }
    public function product() { return $this->belongsTo(\App\Modules\POS\Models\Product::class); }
}

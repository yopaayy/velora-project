<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    use HasUuid;
    public $timestamps = false;
    protected $fillable = ['stock_opname_id','product_id','product_variant_id','system_quantity','actual_quantity','note','created_at'];
    protected function casts(): array { return ['created_at'=>'datetime']; }
    public function opname() { return $this->belongsTo(StockOpname::class,'stock_opname_id'); }
    public function product() { return $this->belongsTo(\App\Modules\POS\Models\Product::class); }
}

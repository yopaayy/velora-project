<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProductWarehouse extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $table = 'product_warehouse';
    protected $fillable = ['product_id','product_variant_id','warehouse_id','business_id','quantity','reserved_quantity','cost_price_avg','last_restock_at'];
    protected function casts(): array { return ['last_restock_at'=>'datetime']; }
    public function product() { return $this->belongsTo(\App\Modules\POS\Models\Product::class); }
    public function variant() { return $this->belongsTo(\App\Modules\POS\Models\ProductVariant::class,'product_variant_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
}

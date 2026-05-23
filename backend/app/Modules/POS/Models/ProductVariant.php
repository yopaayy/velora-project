<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasUuid, BelongsToBusiness, Filterable, SoftDeletes;
    protected $fillable = [
        'product_id','business_id','name','sku','barcode',
        'cost_price','selling_price','min_price','attributes',
        'stock_quantity','stock_alert_qty','image','is_active','sort_order',
    ];
    protected $searchable = ['name','sku','barcode'];
    protected function casts(): array { return ['attributes'=>'array','is_active'=>'boolean']; }
    public function product() { return $this->belongsTo(Product::class); }
}

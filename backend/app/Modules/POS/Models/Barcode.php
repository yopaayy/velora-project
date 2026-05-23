<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','product_id','product_variant_id','code','type'];
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class,'product_variant_id'); }
}

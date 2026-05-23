<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasUuid;
    public $timestamps = false;
    protected $fillable = ['stock_transfer_id','product_id','product_variant_id','quantity_sent','quantity_received','unit_id','base_quantity_sent','note','created_at'];
    protected function casts(): array { return ['created_at'=>'datetime']; }
    public function transfer() { return $this->belongsTo(StockTransfer::class,'stock_transfer_id'); }
    public function product() { return $this->belongsTo(\App\Modules\POS\Models\Product::class); }
}

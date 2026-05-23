<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasUuid, BelongsToBusiness;
    public $timestamps = false;
    protected $fillable = [
        'business_id','product_id','product_variant_id','warehouse_id','batch_id',
        'reference_type','reference_id','movement_type','quantity','unit_id',
        'base_quantity','cost_price','stock_before','stock_after','note','performed_by','created_at',
    ];
    protected function casts(): array { return ['created_at'=>'datetime']; }
    public function product() { return $this->belongsTo(\App\Modules\POS\Models\Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function performer() { return $this->belongsTo(\App\Modules\Auth\Models\User::class,'performed_by'); }
}

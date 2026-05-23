<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','product_id','product_variant_id','warehouse_id','batch_number','quantity','cost_price','manufactured_at','expired_at','status'];
    protected function casts(): array { return ['manufactured_at'=>'date','expired_at'=>'date']; }
    public function product() { return $this->belongsTo(\App\Modules\POS\Models\Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function isExpired(): bool { return $this->expired_at && $this->expired_at->isPast(); }
}

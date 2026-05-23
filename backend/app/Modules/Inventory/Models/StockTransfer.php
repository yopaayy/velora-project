<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','transfer_number','from_warehouse_id','to_warehouse_id','status','note','transferred_by','received_by','transferred_at','received_at'];
    protected function casts(): array { return ['transferred_at'=>'datetime','received_at'=>'datetime']; }
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class,'from_warehouse_id'); }
    public function toWarehouse() { return $this->belongsTo(Warehouse::class,'to_warehouse_id'); }
    public function items() { return $this->hasMany(StockTransferItem::class); }
    public function transferredBy() { return $this->belongsTo(\App\Modules\Auth\Models\User::class,'transferred_by'); }
    public function receivedBy() { return $this->belongsTo(\App\Modules\Auth\Models\User::class,'received_by'); }
}

<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','warehouse_id','adjustment_number','reason','note','status','adjusted_by','approved_by'];
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function items() { return $this->hasMany(StockAdjustmentItem::class); }
    public function adjustedBy() { return $this->belongsTo(\App\Modules\Auth\Models\User::class,'adjusted_by'); }
}

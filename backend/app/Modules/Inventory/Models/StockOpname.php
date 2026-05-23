<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','warehouse_id','opname_number','status','note','started_by','approved_by','started_at','completed_at'];
    protected function casts(): array { return ['started_at'=>'datetime','completed_at'=>'datetime']; }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function items() { return $this->hasMany(StockOpnameItem::class); }
    public function startedBy() { return $this->belongsTo(\App\Modules\Auth\Models\User::class,'started_by'); }
}

<?php
namespace App\Modules\Inventory\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\BelongsToBranch;
use App\Shared\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasUuid, BelongsToBusiness, BelongsToBranch, Filterable, SoftDeletes;
    protected $fillable = ['business_id','branch_id','name','code','type','address','is_active','is_default'];
    protected $searchable = ['name','code'];
    protected function casts(): array { return ['is_active'=>'boolean','is_default'=>'boolean']; }
    public function stocks() { return $this->hasMany(ProductWarehouse::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}

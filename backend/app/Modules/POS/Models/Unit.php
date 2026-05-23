<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;
    protected $fillable = ['business_id','name','symbol','is_base','is_active'];
    protected function casts(): array { return ['is_base'=>'boolean','is_active'=>'boolean']; }
    public function conversionsFrom() { return $this->hasMany(UnitConversion::class,'from_unit_id'); }
    public function conversionsTo() { return $this->hasMany(UnitConversion::class,'to_unit_id'); }
}

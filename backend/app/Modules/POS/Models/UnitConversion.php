<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','from_unit_id','to_unit_id','conversion_factor'];
    public function fromUnit() { return $this->belongsTo(Unit::class,'from_unit_id'); }
    public function toUnit() { return $this->belongsTo(Unit::class,'to_unit_id'); }
}

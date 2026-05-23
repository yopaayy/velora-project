<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','name','slug','logo_url','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function products() { return $this->hasMany(Product::class); }
}

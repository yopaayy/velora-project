<?php
namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\Filterable;
use App\Shared\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuid, BelongsToBusiness, Filterable, Auditable, SoftDeletes;

    protected $fillable = [
        'business_id','category_id','unit_id','name','sku','barcode','description',
        'cost_price','selling_price','min_price','tax_rate','tax_inclusive',
        'type','status','has_variants','track_stock','allow_negative_stock',
        'is_featured','stock_quantity','stock_alert_qty','image',
    ];

    protected $searchable = ['name', 'sku', 'barcode'];

    protected function casts(): array
    {
        return [
            'has_variants'=>'boolean','track_stock'=>'boolean',
            'allow_negative_stock'=>'boolean','is_featured'=>'boolean',
            'tax_inclusive'=>'boolean',
        ];
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
    public function images() { return $this->hasMany(ProductImage::class); }
    public function barcodes() { return $this->hasMany(Barcode::class); }
    public function productUnits() { return $this->hasMany(ProductUnit::class); }
    public function warehouseStocks() { return $this->hasMany(\App\Modules\Inventory\Models\ProductWarehouse::class); }
}

# VELORA — Module POS (Part 1)
## Migrations · Enums · Models

---

## Folder Structure

```
app/Modules/POS/
├── Controllers/
│   ├── CategoryController.php
│   ├── UnitController.php
│   ├── ProductController.php
│   └── ProductVariantController.php
├── DTOs/
│   ├── CreateProductDTO.php
│   └── UpdateProductDTO.php
├── Enums/
│   ├── ProductStatus.php
│   └── ProductType.php
├── Models/
│   ├── Category.php
│   ├── Unit.php
│   ├── Product.php
│   ├── ProductVariant.php
│   └── ProductImage.php
├── Repositories/
│   ├── Contracts/
│   │   ├── CategoryRepositoryInterface.php
│   │   ├── UnitRepositoryInterface.php
│   │   └── ProductRepositoryInterface.php
│   └── Eloquent/
│       ├── CategoryRepository.php
│       ├── UnitRepository.php
│       └── ProductRepository.php
├── Requests/
│   ├── StoreCategoryRequest.php
│   ├── StoreUnitRequest.php
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
├── Resources/
│   ├── CategoryResource.php
│   ├── UnitResource.php
│   ├── ProductResource.php
│   └── ProductVariantResource.php
├── Services/
│   ├── CategoryService.php
│   ├── UnitService.php
│   └── ProductService.php
└── Routes/
    └── api.php
```

---

## 1. Migrations

### Categories Table

```php
<?php
// database/migrations/pos/2026_05_19_000020_create_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('parent_id')->nullable()->index(); // sub-category

            $table->string('name', 100);
            $table->string('slug', 110)->nullable();
            $table->string('color', 7)->nullable()->comment('Hex color for POS display');
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();

            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void { Schema::dropIfExists('categories'); }
};
```

### Units Table

```php
<?php
// database/migrations/pos/2026_05_19_000021_create_units_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            $table->string('name', 50);       // Kilogram, Liter, Pcs
            $table->string('symbol', 10);     // kg, L, pcs
            $table->boolean('is_base')->default(false)->comment('Base unit for conversion');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'symbol']);
            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('units'); }
};
```

### Products Table

```php
<?php
// database/migrations/pos/2026_05_19_000022_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('unit_id')->nullable();

            $table->string('name', 200);
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->text('description')->nullable();

            // Pricing
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->bigInteger('min_price')->default(0)->comment('Minimum sale price');
            $table->tinyInteger('tax_rate')->default(0)->comment('% PPN, 0 = no tax');
            $table->boolean('tax_inclusive')->default(false);

            // Type & Status
            $table->string('type', 20)->default('simple');     // simple, variant, service, bundle
            $table->string('status', 20)->default('active');   // active, inactive, draft

            // Flags
            $table->boolean('has_variants')->default(false);
            $table->boolean('track_stock')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_featured')->default(false);

            // Stock (for simple products, no variants)
            $table->integer('stock_quantity')->default(0);
            $table->integer('stock_alert_qty')->default(5)->comment('Low stock alert threshold');

            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();

            $table->unique(['business_id', 'sku']);
            $table->unique(['business_id', 'barcode']);
            $table->index(['business_id', 'status', 'is_featured']);
            $table->index(['business_id', 'category_id', 'status']);
            $table->index(['business_id', 'track_stock', 'stock_quantity']);
            $table->fullText(['name', 'sku', 'barcode'], 'products_search');
        });
    }

    public function down(): void { Schema::dropIfExists('products'); }
};
```

### Product Variants Table

```php
<?php
// database/migrations/pos/2026_05_19_000023_create_product_variants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('business_id');

            $table->string('name', 150);        // e.g. "Merah - L", "Kopi Susu - Hot"
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();

            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->bigInteger('min_price')->default(0);

            $table->json('attributes')->nullable()->comment('{"size":"L","color":"Merah"}');

            $table->integer('stock_quantity')->default(0);
            $table->integer('stock_alert_qty')->default(5);

            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();

            $table->unique(['business_id', 'sku']);
            $table->unique(['business_id', 'barcode']);
            $table->index(['product_id', 'is_active']);
            $table->index(['business_id', 'stock_quantity']);
        });
    }

    public function down(): void { Schema::dropIfExists('product_variants'); }
};
```

### Product Images Table

```php
<?php
// database/migrations/pos/2026_05_19_000024_create_product_images_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['product_id', 'is_primary']);
        });
    }

    public function down(): void { Schema::dropIfExists('product_images'); }
};
```

---

## 2. Enums

### `app/Modules/POS/Enums/ProductStatus.php`

```php
<?php

namespace App\Modules\POS\Enums;

enum ProductStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Draft    = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Aktif',
            self::Inactive => 'Tidak Aktif',
            self::Draft    => 'Draft',
        };
    }

    public function isAvailableForSale(): bool
    {
        return $this === self::Active;
    }
}
```

### `app/Modules/POS/Enums/ProductType.php`

```php
<?php

namespace App\Modules\POS\Enums;

enum ProductType: string
{
    case Simple  = 'simple';
    case Variant = 'variant';
    case Service = 'service';
    case Bundle  = 'bundle';

    public function label(): string
    {
        return match ($this) {
            self::Simple  => 'Produk Biasa',
            self::Variant => 'Produk dengan Varian',
            self::Service => 'Jasa / Layanan',
            self::Bundle  => 'Bundel / Paket',
        };
    }

    public function requiresStock(): bool
    {
        return in_array($this, [self::Simple, self::Variant, self::Bundle]);
    }
}
```

---

## 3. Models

### `app/Modules/POS/Models/Category.php`

```php
<?php

namespace App\Modules\POS\Models;

use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'parent_id',
        'name', 'slug', 'color', 'icon',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /* ── Relations ── */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
```

### `app/Modules/POS/Models/Unit.php`

```php
<?php

namespace App\Modules\POS\Models;

use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'name', 'symbol', 'is_base', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_base'   => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### `app/Modules/POS/Models/Product.php`

```php
<?php

namespace App\Modules\POS\Models;

use App\Modules\POS\Enums\ProductStatus;
use App\Modules\POS\Enums\ProductType;
use App\Shared\Helpers\CodeGenerator;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'category_id', 'unit_id',
        'name', 'sku', 'barcode', 'description',
        'cost_price', 'selling_price', 'min_price',
        'tax_rate', 'tax_inclusive',
        'type', 'status',
        'has_variants', 'track_stock', 'allow_negative_stock', 'is_featured',
        'stock_quantity', 'stock_alert_qty', 'image',
    ];

    protected function casts(): array
    {
        return [
            'status'               => ProductStatus::class,
            'type'                 => ProductType::class,
            'has_variants'         => 'boolean',
            'track_stock'          => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_featured'          => 'boolean',
            'tax_inclusive'        => 'boolean',
            'cost_price'           => 'integer',
            'selling_price'        => 'integer',
            'min_price'            => 'integer',
            'stock_quantity'       => 'integer',
            'stock_alert_qty'      => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->sku)) {
                $model->sku = CodeGenerator::sku();
            }
        });
    }

    /* ── Relations ── */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')
                    ->orderBy('sort_order');
    }

    /* ── Accessors ── */

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? \Illuminate\Support\Facades\Storage::url($this->image)
            : null;
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
                     ->whereColumn('stock_quantity', '<=', 'stock_alert_qty');
    }

    /* ── Helpers ── */

    public function isAvailableForSale(): bool
    {
        return $this->status->isAvailableForSale();
    }

    public function hasEnoughStock(int $qty): bool
    {
        if (!$this->track_stock || $this->allow_negative_stock) {
            return true;
        }
        return $this->stock_quantity >= $qty;
    }

    public function getPriceWithTax(): int
    {
        if ($this->tax_rate === 0 || $this->tax_inclusive) {
            return $this->selling_price;
        }
        return (int) round($this->selling_price * (1 + $this->tax_rate / 100));
    }
}
```

### `app/Modules/POS/Models/ProductVariant.php`

```php
<?php

namespace App\Modules\POS\Models;

use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'product_id', 'business_id',
        'name', 'sku', 'barcode',
        'cost_price', 'selling_price', 'min_price',
        'attributes', 'stock_quantity', 'stock_alert_qty',
        'image', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'attributes'     => 'array',
            'is_active'      => 'boolean',
            'cost_price'     => 'integer',
            'selling_price'  => 'integer',
            'min_price'      => 'integer',
            'stock_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function hasEnoughStock(int $qty): bool
    {
        $product = $this->product;
        if (!$product->track_stock || $product->allow_negative_stock) {
            return true;
        }
        return $this->stock_quantity >= $qty;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? \Illuminate\Support\Facades\Storage::url($this->image)
            : null;
    }
}
```

### `app/Modules/POS/Models/ProductImage.php`

```php
<?php

namespace App\Modules\POS\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasUuid;

    protected $fillable = [
        'id', 'product_id', 'path', 'is_primary', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::url($this->path);
    }
}
```

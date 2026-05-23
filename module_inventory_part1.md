# VELORA — Module Inventory (Part 1)
## Migrations · Enums · Models

---

## Folder Structure

```
app/Modules/Inventory/
├── Controllers/
│   ├── WarehouseController.php
│   └── StockMovementController.php
├── DTOs/
│   ├── CreateWarehouseDTO.php
│   └── CreateStockMovementDTO.php
├── Enums/
│   ├── MovementType.php
│   └── MovementStatus.php
├── Models/
│   ├── Warehouse.php
│   ├── StockMovement.php
│   └── StockMovementItem.php
├── Repositories/
│   ├── Contracts/
│   │   ├── WarehouseRepositoryInterface.php
│   │   └── StockMovementRepositoryInterface.php
│   └── Eloquent/
│       ├── WarehouseRepository.php
│       └── StockMovementRepository.php
├── Requests/
│   ├── StoreWarehouseRequest.php
│   └── StoreStockMovementRequest.php
├── Resources/
│   ├── WarehouseResource.php
│   └── StockMovementResource.php
├── Services/
│   ├── WarehouseService.php
│   └── StockMovementService.php
└── Routes/
    └── api.php
```

---

## 1. Migrations

### Warehouses Table

```php
<?php
// database/migrations/inventory/2026_05_19_000030_create_warehouses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable()->comment('Terkait dengan cabang mana');

            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'is_active', 'is_main']);
        });
    }

    public function down(): void { Schema::dropIfExists('warehouses'); }
};
```

### Stock Movements Table

```php
<?php
// database/migrations/inventory/2026_05_19_000031_create_stock_movements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('warehouse_id');
            $table->uuid('user_id')->comment('Siapa yang membuat movement');

            $table->string('reference_number', 50)->unique();
            $table->string('type', 20)->comment('in, out, transfer, adjustment, opname, return');
            $table->string('status', 20)->default('completed')->comment('draft, pending, completed, cancelled');
            $table->text('notes')->nullable();

            // Referensi ke transaksi lain (misal ID Sales / Purchase Order)
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // misal 'sales', 'purchases'

            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['business_id', 'type', 'status']);
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
```

### Stock Movement Items Table

```php
<?php
// database/migrations/inventory/2026_05_19_000032_create_stock_movement_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movement_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_movement_id');
            $table->uuid('product_id');
            $table->uuid('product_variant_id')->nullable();

            $table->integer('quantity'); // Positif/Negatif akan disesuaikan saat hitung, di sini simpan absolute qty
            $table->integer('before_quantity')->default(0);
            $table->integer('after_quantity')->default(0);

            $table->bigInteger('cost_price')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }

    public function down(): void { Schema::dropIfExists('stock_movement_items'); }
};
```

---

## 2. Enums

### `app/Modules/Inventory/Enums/MovementType.php`

```php
<?php

namespace App\Modules\Inventory\Enums;

enum MovementType: string
{
    case In         = 'in';         // Barang masuk (manual/pembelian)
    case Out        = 'out';        // Barang keluar (manual/kerusakan)
    case Transfer   = 'transfer';   // Pindah gudang
    case Adjustment = 'adjustment'; // Penyesuaian stok (bisa in/out)
    case Opname     = 'opname';     // Hasil stock opname (hitung fisik)
    case Sale       = 'sale';       // Terjual via POS
    case Return     = 'return';     // Retur pelanggan

    public function label(): string
    {
        return match ($this) {
            self::In         => 'Barang Masuk',
            self::Out        => 'Barang Keluar',
            self::Transfer   => 'Transfer Antar Gudang',
            self::Adjustment => 'Penyesuaian Stok',
            self::Opname     => 'Stock Opname',
            self::Sale       => 'Penjualan',
            self::Return     => 'Retur Pelanggan',
        };
    }

    public function isAddition(): bool
    {
        return in_array($this, [self::In, self::Return]);
    }

    public function isDeduction(): bool
    {
        return in_array($this, [self::Out, self::Sale]);
    }
}
```

### `app/Modules/Inventory/Enums/MovementStatus.php`

```php
<?php

namespace App\Modules\Inventory\Enums;

enum MovementStatus: string
{
    case Draft     = 'draft';
    case Pending   = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Pending   => 'Menunggu',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
```

---

## 3. Models

### `app/Modules/Inventory/Models/Warehouse.php`

```php
<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Tenant\Models\Branch;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'branch_id',
        'name', 'code', 'address',
        'is_main', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main'   => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /* ── Relations ── */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### `app/Modules/Inventory/Models/StockMovement.php`

```php
<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Inventory\Enums\MovementStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Shared\Helpers\CodeGenerator;

class StockMovement extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'id', 'business_id', 'warehouse_id', 'user_id',
        'reference_number', 'type', 'status', 'notes',
        'reference_id', 'reference_type',
    ];

    protected function casts(): array
    {
        return [
            'type'   => MovementType::class,
            'status' => MovementStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $prefix = strtoupper(substr($model->type->value, 0, 3));
                $model->reference_number = CodeGenerator::invoiceNumber($prefix);
            }
        });
    }

    /* ── Relations ── */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockMovementItem::class, 'stock_movement_id');
    }
}
```

### `app/Modules/Inventory/Models/StockMovementItem.php`

```php
<?php

namespace App\Modules\Inventory\Models;

use App\Modules\POS\Models\Product;
use App\Modules\POS\Models\ProductVariant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'id', 'stock_movement_id', 'product_id', 'product_variant_id',
        'quantity', 'before_quantity', 'after_quantity',
        'cost_price', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'        => 'integer',
            'before_quantity' => 'integer',
            'after_quantity'  => 'integer',
            'cost_price'      => 'integer',
        ];
    }

    /* ── Relations ── */

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
```

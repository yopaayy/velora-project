# VELORA — Module Inventory (Part 3)
## Services · Controllers · Routes

---

## 10. Services

### `app/Modules/Inventory/Services/WarehouseService.php`

```php
<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\DTOs\CreateWarehouseDTO;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Shared\Exceptions\VeloraException;
use App\Shared\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class WarehouseService extends BaseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $repo
    ) {}

    public function getAll(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return $this->repo->findByBusiness($businessId, $filters);
    }

    public function findOrFail(string $id): Warehouse
    {
        $warehouse = $this->repo->findById($id);

        if (!$warehouse || $warehouse->business_id !== app('current.business_id')) {
            throw VeloraException::notFound('Warehouse');
        }

        return $warehouse;
    }

    public function create(CreateWarehouseDTO $dto): Warehouse
    {
        $warehouse = $this->repo->create($dto->toArray());

        if ($dto->isMain) {
            $this->repo->setMainWarehouse($warehouse->id, $dto->businessId);
        }

        return $warehouse->fresh(['branch']);
    }

    public function update(string $id, array $data): Warehouse
    {
        $warehouse = $this->findOrFail($id);
        
        $updated = $this->repo->update($warehouse->id, $data);
        
        if (isset($data['is_main']) && $data['is_main']) {
            $this->repo->setMainWarehouse($warehouse->id, $warehouse->business_id);
        }
        
        return $updated->fresh(['branch']);
    }
    
    public function setAsMain(string $id): Warehouse
    {
        $warehouse = $this->findOrFail($id);
        $this->repo->setMainWarehouse($warehouse->id, $warehouse->business_id);
        
        return $warehouse->fresh(['branch']);
    }

    public function delete(string $id): void
    {
        $warehouse = $this->findOrFail($id);
        
        if ($warehouse->is_main) {
            throw new VeloraException('Tidak dapat menghapus gudang utama.', 422, 'CANNOT_DELETE_MAIN_WAREHOUSE');
        }
        
        // Cek apakah ada stock movement
        if ($warehouse->movements()->count() > 0) {
            throw new VeloraException('Tidak dapat menghapus gudang yang memiliki riwayat pergerakan stok.', 422, 'WAREHOUSE_HAS_MOVEMENTS');
        }
        
        $this->repo->delete($warehouse->id);
    }
}
```

### `app/Modules/Inventory/Services/StockMovementService.php`

```php
<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\DTOs\CreateStockMovementDTO;
use App\Modules\Inventory\Enums\MovementStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Modules\POS\Repositories\Contracts\ProductRepositoryInterface;
use App\Shared\Exceptions\VeloraException;
use App\Shared\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockMovementService extends BaseService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movementRepo,
        private readonly ProductRepositoryInterface       $productRepo
    ) {}

    public function getAll(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return $this->movementRepo->findByBusiness($businessId, $filters);
    }

    public function findOrFail(string $id): StockMovement
    {
        $movement = $this->movementRepo->findById($id);

        if (!$movement || $movement->business_id !== app('current.business_id')) {
            throw VeloraException::notFound('Stock Movement');
        }

        return $movement;
    }

    public function processMovement(CreateStockMovementDTO $dto): StockMovement
    {
        return DB::transaction(function () use ($dto) {
            
            // 1. Create header
            $movementData = [
                'business_id'    => $dto->businessId,
                'warehouse_id'   => $dto->warehouseId,
                'user_id'        => $dto->userId,
                'type'           => $dto->type,
                'status'         => $dto->status,
                'notes'          => $dto->notes,
                'reference_id'   => $dto->referenceId,
                'reference_type' => $dto->referenceType,
            ];
            
            $movement = $this->movementRepo->create($movementData);
            $typeEnum = MovementType::from($dto->type);
            $isAddition = $typeEnum->isAddition();
            
            // 2. Process items
            foreach ($dto->items as $itemData) {
                
                $productId = $itemData['product_id'];
                $variantId = $itemData['variant_id'] ?? null;
                $qtyToMove = abs((int) $itemData['quantity']); 
                
                // Opname logic is different (qty is the actual physical count)
                if ($typeEnum === MovementType::Opname) {
                    $this->processOpnameItem($movement, $productId, $variantId, $qtyToMove, $itemData);
                    continue;
                }
                
                // Regular IN/OUT logic
                $this->processRegularItem($movement, $productId, $variantId, $qtyToMove, $isAddition, $itemData);
            }

            return $movement->fresh(['warehouse', 'user', 'items.product', 'items.variant']);
        });
    }
    
    private function processRegularItem(StockMovement $movement, string $productId, ?string $variantId, int $qtyToMove, bool $isAddition, array $itemData): void
    {
        $product = $this->productRepo->findById($productId);
        
        if (!$product) {
            throw new VeloraException("Produk tidak ditemukan.", 404, 'PRODUCT_NOT_FOUND');
        }
        
        if (!$product->track_stock) {
            return; // Skip non-stockable products (e.g. services)
        }
        
        $beforeQty = 0;
        $afterQty = 0;
        
        // Handle Variant
        if ($variantId) {
            $variant = $product->variants()->where('id', $variantId)->first();
            if (!$variant) throw new VeloraException("Varian produk tidak ditemukan.", 404, 'VARIANT_NOT_FOUND');
            
            $beforeQty = $variant->stock_quantity;
            $afterQty = $isAddition ? ($beforeQty + $qtyToMove) : ($beforeQty - $qtyToMove);
            
            if (!$isAddition && !$product->allow_negative_stock && $afterQty < 0) {
                throw new VeloraException("Stok tidak mencukupi untuk {$product->name} ({$variant->name}).", 422, 'INSUFFICIENT_STOCK');
            }
            
            if ($movement->status->value === MovementStatus::Completed->value) {
                $this->productRepo->updateVariant($variant->id, ['stock_quantity' => $afterQty]);
            }
            
        } 
        // Handle Simple Product
        else {
            $beforeQty = $product->stock_quantity;
            $afterQty = $isAddition ? ($beforeQty + $qtyToMove) : ($beforeQty - $qtyToMove);
            
            if (!$isAddition && !$product->allow_negative_stock && $afterQty < 0) {
                throw new VeloraException("Stok tidak mencukupi untuk {$product->name}.", 422, 'INSUFFICIENT_STOCK');
            }
            
            if ($movement->status->value === MovementStatus::Completed->value) {
                $this->productRepo->update($product->id, ['stock_quantity' => $afterQty]);
            }
        }
        
        // Create Item Record
        $this->movementRepo->createItem([
            'stock_movement_id'  => $movement->id,
            'product_id'         => $productId,
            'product_variant_id' => $variantId,
            'quantity'           => $isAddition ? $qtyToMove : -$qtyToMove, // simpan minus untuk pengeluaran agar jelas
            'before_quantity'    => $beforeQty,
            'after_quantity'     => $afterQty,
            'cost_price'         => $itemData['cost_price'] ?? ($variantId ? $variant->cost_price : $product->cost_price),
            'notes'              => $itemData['notes'] ?? null,
        ]);
    }
    
    private function processOpnameItem(StockMovement $movement, string $productId, ?string $variantId, int $physicalQty, array $itemData): void
    {
        $product = $this->productRepo->findById($productId);
        if (!$product || !$product->track_stock) return;
        
        $beforeQty = 0;
        
        if ($variantId) {
            $variant = $product->variants()->where('id', $variantId)->first();
            $beforeQty = $variant->stock_quantity;
            
            if ($movement->status->value === MovementStatus::Completed->value) {
                $this->productRepo->updateVariant($variant->id, ['stock_quantity' => $physicalQty]);
            }
        } else {
            $beforeQty = $product->stock_quantity;
            
            if ($movement->status->value === MovementStatus::Completed->value) {
                $this->productRepo->update($product->id, ['stock_quantity' => $physicalQty]);
            }
        }
        
        $diff = $physicalQty - $beforeQty;
        
        $this->movementRepo->createItem([
            'stock_movement_id'  => $movement->id,
            'product_id'         => $productId,
            'product_variant_id' => $variantId,
            'quantity'           => $diff, // Selisih stok
            'before_quantity'    => $beforeQty,
            'after_quantity'     => $physicalQty, // Stok hasil opname (fisik)
            'cost_price'         => $itemData['cost_price'] ?? 0,
            'notes'              => $itemData['notes'] ?? null,
        ]);
    }
}
```

---

## 11. Controllers

### `app/Modules/Inventory/Controllers/WarehouseController.php`

```php
<?php

namespace App\Modules\Inventory\Controllers;

use App\Modules\Inventory\DTOs\CreateWarehouseDTO;
use App\Modules\Inventory\Requests\StoreWarehouseRequest;
use App\Modules\Inventory\Resources\WarehouseResource;
use App\Modules\Inventory\Services\WarehouseService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends BaseController
{
    public function __construct(private readonly WarehouseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $paginator  = $this->service->getAll($businessId, $request->all());
        
        return $this->paginated($paginator, WarehouseResource::collection($paginator));
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $warehouse = $this->service->create(
            CreateWarehouseDTO::fromRequest($request->validated(), $businessId)
        );
        
        return $this->created(new WarehouseResource($warehouse), 'Gudang berhasil ditambahkan.');
    }

    public function show(string $id): JsonResponse
    {
        $warehouse = $this->service->findOrFail($id);
        return $this->success(new WarehouseResource($warehouse));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:100'],
            'branch_id' => ['nullable', 'uuid'],
            'address'   => ['nullable', 'string'],
            'is_main'   => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        
        $warehouse = $this->service->update($id, $data);
        return $this->success(new WarehouseResource($warehouse), 'Gudang berhasil diperbarui.');
    }
    
    public function setMain(string $id): JsonResponse
    {
        $warehouse = $this->service->setAsMain($id);
        return $this->success(new WarehouseResource($warehouse), 'Gudang diatur sebagai gudang utama.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->noContent();
    }
}
```

### `app/Modules/Inventory/Controllers/StockMovementController.php`

```php
<?php

namespace App\Modules\Inventory\Controllers;

use App\Modules\Inventory\DTOs\CreateStockMovementDTO;
use App\Modules\Inventory\Requests\StoreStockMovementRequest;
use App\Modules\Inventory\Resources\StockMovementResource;
use App\Modules\Inventory\Services\StockMovementService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends BaseController
{
    public function __construct(private readonly StockMovementService $service) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $paginator  = $this->service->getAll($businessId, $request->all());
        
        return $this->paginated($paginator, StockMovementResource::collection($paginator));
    }

    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $userId = $request->user()->id;
        
        $movement = $this->service->processMovement(
            CreateStockMovementDTO::fromRequest($request->validated(), $businessId, $userId)
        );
        
        return $this->created(new StockMovementResource($movement), 'Pergerakan stok berhasil dicatat.');
    }

    public function show(string $id): JsonResponse
    {
        $movement = $this->service->findOrFail($id);
        return $this->success(new StockMovementResource($movement));
    }
}
```

---

## 12. Routes

### `app/Modules/Inventory/Routes/api.php`

```php
<?php

use App\Modules\Inventory\Controllers\StockMovementController;
use App\Modules\Inventory\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.scope', 'subscription.active'])->group(function () {
    
    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('warehouses/{warehouse}/set-main', [WarehouseController::class, 'setMain'])->name('warehouses.set-main');
    
    // Stock Movements
    Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store', 'show']);
    
});
```

---

## Checklist Module Inventory

- [x] Migrations: `warehouses`, `stock_movements`, `stock_movement_items`
- [x] Enums: `MovementType`, `MovementStatus`
- [x] Models: `Warehouse`, `StockMovement`, `StockMovementItem`
- [x] Repositories: Interface + Eloquent untuk Warehouse & StockMovement
- [x] DTOs: `CreateWarehouseDTO`, `CreateStockMovementDTO`
- [x] Form Requests: `StoreWarehouseRequest`, `StoreStockMovementRequest` (validasi structure array items)
- [x] Resources: `WarehouseResource`, `StockMovementResource` (with map items)
- [x] Services: `WarehouseService` (setMain, validation before delete), `StockMovementService` (handling DB transaction stock update, logic regular IN/OUT vs Opname)
- [x] Controllers: `WarehouseController`, `StockMovementController`
- [x] Routes: Warehouses CRUD, Stock Movements List & Create

Dengan ini, modul POS dan Inventory sudah saling terintegrasi (menambah movement akan langsung memotong/menambah stok di tabel produk/varian jika statusnya `completed`).

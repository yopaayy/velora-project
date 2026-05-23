# VELORA — Module Sales (Part 3)
## Services · Controllers · Routes

---

## 10. Services

### `app/Modules/Sales/Services/ShiftService.php`

```php
<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\DTOs\CloseShiftDTO;
use App\Modules\Sales\DTOs\CreateShiftDTO;
use App\Modules\Sales\Enums\ShiftStatus;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Repositories\Contracts\ShiftRepositoryInterface;
use App\Modules\Sales\Repositories\Contracts\TransactionRepositoryInterface;
use App\Shared\Exceptions\VeloraException;
use App\Shared\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class ShiftService extends BaseService
{
    public function __construct(
        private readonly ShiftRepositoryInterface $shiftRepo,
        private readonly TransactionRepositoryInterface $transactionRepo
    ) {}

    public function getAll(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return $this->shiftRepo->findByBusiness($businessId, $filters);
    }

    public function findOrFail(string $id): Shift
    {
        $shift = $this->shiftRepo->findById($id);

        if (!$shift || $shift->business_id !== app('current.business_id')) {
            throw VeloraException::notFound('Shift');
        }

        return $shift;
    }

    public function getActiveShift(string $businessId, string $userId): ?Shift
    {
        return $this->shiftRepo->findActiveByUser($businessId, $userId);
    }

    public function openShift(CreateShiftDTO $dto): Shift
    {
        $activeShift = $this->getActiveShift($dto->businessId, $dto->userId);
        
        if ($activeShift) {
            throw new VeloraException('Anda masih memiliki shift yang aktif. Tutup shift sebelumnya terlebih dahulu.', 422, 'SHIFT_ALREADY_OPEN');
        }

        return $this->shiftRepo->create([
            'business_id'   => $dto->businessId,
            'branch_id'     => $dto->branchId,
            'user_id'       => $dto->userId,
            'opened_at'     => now(),
            'starting_cash' => $dto->startingCash,
            'status'        => ShiftStatus::Open->value,
            'notes'         => $dto->notes,
        ]);
    }

    public function closeShift(string $shiftId, CloseShiftDTO $dto): Shift
    {
        $shift = $this->findOrFail($shiftId);

        if (!$shift->isOpen()) {
            throw new VeloraException('Shift ini sudah ditutup sebelumnya.', 422, 'SHIFT_ALREADY_CLOSED');
        }

        // Calculate expected cash
        // expected = starting_cash + total_cash_sales_in_this_shift
        $totalCashSales = $this->transactionRepo->sumGrandTotalByShift($shift->id);
        
        $expectedEndingCash = $shift->starting_cash + $totalCashSales;
        $difference         = $dto->actualEndingCash - $expectedEndingCash;

        return $this->shiftRepo->update($shift->id, [
            'status'               => ShiftStatus::Closed->value,
            'closed_at'            => now(),
            'actual_ending_cash'   => $dto->actualEndingCash,
            'expected_ending_cash' => $expectedEndingCash,
            'difference'           => $difference,
            'notes'                => $dto->notes,
        ]);
    }
}
```

### `app/Modules/Sales/Services/TransactionService.php`

```php
<?php

namespace App\Modules\Sales\Services;

use App\Modules\Inventory\DTOs\CreateStockMovementDTO;
use App\Modules\Inventory\Enums\MovementStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\POS\Repositories\Contracts\ProductRepositoryInterface;
use App\Modules\Sales\DTOs\CreateTransactionDTO;
use App\Modules\Sales\Enums\TransactionStatus;
use App\Modules\Sales\Models\Transaction;
use App\Modules\Sales\Repositories\Contracts\TransactionRepositoryInterface;
use App\Shared\Exceptions\VeloraException;
use App\Shared\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService extends BaseService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepo,
        private readonly ProductRepositoryInterface     $productRepo,
        private readonly StockMovementService           $stockMovementService
    ) {}

    public function getAll(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return $this->transactionRepo->findByBusiness($businessId, $filters);
    }

    public function findOrFail(string $id): Transaction
    {
        $transaction = $this->transactionRepo->findById($id);

        if (!$transaction || $transaction->business_id !== app('current.business_id')) {
            throw VeloraException::notFound('Transaction');
        }

        return $transaction;
    }

    public function createTransaction(CreateTransactionDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            
            // 1. Prepare items & calculate totals
            $subtotal    = 0;
            $itemsData   = [];
            $stockItems  = [];
            
            // Note: Untuk kemudahan, diasumsikan transaksi selalu berasal dari warehouse/cabang yang sama dengan user/shift
            // Anda bisa mengatur mapping `branch_id` ke `warehouse_id` utama di cabang tersebut.
            $warehouseId = $this->getMainWarehouseIdForBranch($dto->branchId);

            foreach ($dto->items as $itemReq) {
                $product = $this->productRepo->findById($itemReq['product_id']);
                if (!$product) throw VeloraException::notFound("Product {$itemReq['product_id']} not found");
                
                $variant = null;
                if (!empty($itemReq['variant_id'])) {
                    $variant = $product->variants()->where('id', $itemReq['variant_id'])->first();
                    if (!$variant) throw VeloraException::notFound("Variant {$itemReq['variant_id']} not found");
                }
                
                $unitPrice = $variant ? $variant->selling_price : $product->selling_price;
                $costPrice = $variant ? $variant->cost_price : $product->cost_price;
                $qty       = (int) $itemReq['quantity'];
                
                $itemSubtotal = ($unitPrice * $qty) - ($itemReq['discount_amount'] ?? 0);
                
                $itemsData[] = [
                    'product_id'         => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name'       => $product->name,
                    'variant_name'       => $variant?->name,
                    'sku'                => $variant ? $variant->sku : $product->sku,
                    'quantity'           => $qty,
                    'unit_price'         => $unitPrice,
                    'cost_price'         => $costPrice,
                    'discount_amount'    => $itemReq['discount_amount'] ?? 0,
                    'tax_amount'         => 0, // Simplified tax calculation per item
                    'subtotal'           => $itemSubtotal,
                    'notes'              => $itemReq['notes'] ?? null,
                ];
                
                $subtotal += $itemSubtotal;
                
                // Prepare stock deduction data if product tracks stock
                if ($product->track_stock && $warehouseId) {
                    $stockItems[] = [
                        'product_id' => $product->id,
                        'variant_id' => $variant?->id,
                        'quantity'   => $qty,
                        'cost_price' => $costPrice,
                    ];
                }
            }

            // 2. Finalize Header amounts
            $grandTotal = $subtotal - $dto->discountAmount + $dto->taxAmount + $dto->serviceCharge;
            $change     = $dto->amountPaid - $grandTotal;
            
            if ($change < 0 && $dto->paymentMethodId !== null) {
                // Kecuali payment type credit/piutang
                throw new VeloraException('Jumlah pembayaran kurang dari total tagihan.', 422, 'INSUFFICIENT_PAYMENT');
            }

            // 3. Create Transaction Header
            $transaction = $this->transactionRepo->create([
                'business_id'       => $dto->businessId,
                'branch_id'         => $dto->branchId,
                'user_id'           => $dto->userId,
                'shift_id'          => $dto->shiftId,
                'payment_method_id' => $dto->paymentMethodId,
                'customer_id'       => $dto->customerId,
                'status'            => TransactionStatus::Completed->value,
                'payment_status'    => 'paid',
                'order_type'        => $dto->orderType,
                'subtotal'          => $subtotal,
                'discount_amount'   => $dto->discountAmount,
                'tax_amount'        => $dto->taxAmount,
                'service_charge'    => $dto->serviceCharge,
                'grand_total'       => $grandTotal,
                'amount_paid'       => $dto->amountPaid,
                'change_amount'     => max(0, $change),
                'payment_reference' => $dto->paymentReference,
                'notes'             => $dto->notes,
            ]);

            // 4. Create Transaction Items
            foreach ($itemsData as $item) {
                $item['transaction_id'] = $transaction->id;
                $this->transactionRepo->createItem($item);
            }

            // 5. Deduct Stock via Inventory Module
            if (!empty($stockItems) && $warehouseId) {
                $movementDto = new CreateStockMovementDTO(
                    businessId:    $dto->businessId,
                    warehouseId:   $warehouseId,
                    userId:        $dto->userId,
                    type:          MovementType::Sale->value,
                    status:        MovementStatus::Completed->value,
                    referenceId:   $transaction->id,
                    referenceType: 'sales',
                    items:         $stockItems,
                );
                
                // Ini akan melemparkan INSUFFICIENT_STOCK jika tidak cukup & allow_negative_stock=false
                $this->stockMovementService->processMovement($movementDto);
            }

            return $transaction->fresh(['items', 'paymentMethod']);
        });
    }
    
    private function getMainWarehouseIdForBranch(string $branchId): ?string
    {
        // Implementasi sederhana: Ambil gudang pertama yang ada di branch ini
        $warehouse = \App\Modules\Inventory\Models\Warehouse::where('branch_id', $branchId)
            ->orderByDesc('is_main')
            ->first();
            
        return $warehouse?->id;
    }
}
```

---

## 11. Controllers

### `app/Modules/Sales/Controllers/ShiftController.php`

```php
<?php

namespace App\Modules\Sales\Controllers;

use App\Modules\Sales\DTOs\CloseShiftDTO;
use App\Modules\Sales\DTOs\CreateShiftDTO;
use App\Modules\Sales\Requests\CloseShiftRequest;
use App\Modules\Sales\Requests\OpenShiftRequest;
use App\Modules\Sales\Resources\ShiftResource;
use App\Modules\Sales\Services\ShiftService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends BaseController
{
    public function __construct(private readonly ShiftService $service) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $paginator  = $this->service->getAll($businessId, $request->all());
        
        return $this->paginated($paginator, ShiftResource::collection($paginator));
    }

    public function current(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $shift = $this->service->getActiveShift($businessId, $request->user()->id);
        
        if (!$shift) {
            return $this->success(null, 'Tidak ada shift aktif.');
        }
        
        return $this->success(new ShiftResource($shift));
    }

    public function open(OpenShiftRequest $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $userId     = $request->user()->id;
        
        $shift = $this->service->openShift(
            CreateShiftDTO::fromRequest($request->validated(), $businessId, $userId)
        );
        
        return $this->created(new ShiftResource($shift), 'Shift berhasil dibuka.');
    }

    public function close(CloseShiftRequest $request, string $id): JsonResponse
    {
        $shift = $this->service->closeShift(
            $id,
            CloseShiftDTO::fromRequest($request->validated())
        );
        
        return $this->success(new ShiftResource($shift), 'Shift berhasil ditutup.');
    }
}
```

### `app/Modules/Sales/Controllers/TransactionController.php`

```php
<?php

namespace App\Modules\Sales\Controllers;

use App\Modules\Sales\DTOs\CreateTransactionDTO;
use App\Modules\Sales\Requests\StoreTransactionRequest;
use App\Modules\Sales\Resources\TransactionResource;
use App\Modules\Sales\Services\ShiftService;
use App\Modules\Sales\Services\TransactionService;
use App\Shared\Controllers\BaseController;
use App\Shared\Exceptions\VeloraException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends BaseController
{
    public function __construct(
        private readonly TransactionService $service,
        private readonly ShiftService       $shiftService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $paginator  = $this->service->getAll($businessId, $request->all());
        
        return $this->paginated($paginator, TransactionResource::collection($paginator));
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $userId     = $request->user()->id;
        
        // Asumsi header dikirim / atau cek setting apakah wajib shift
        $requireShift = app('current.business')->getSetting('require_shift', true);
        $shiftId      = null;
        $branchId     = $request->header('X-Branch-ID');
        
        if (!$branchId) {
            throw new VeloraException('Header X-Branch-ID wajib disertakan saat transaksi.', 400);
        }

        if ($requireShift) {
            $activeShift = $this->shiftService->getActiveShift($businessId, $userId);
            if (!$activeShift) {
                throw new VeloraException('Anda harus membuka shift (kasir) sebelum melakukan transaksi.', 403, 'SHIFT_NOT_OPEN');
            }
            $shiftId  = $activeShift->id;
            $branchId = $activeShift->branch_id; // Timpa dengan branch dari shift
        }

        $transaction = $this->service->createTransaction(
            CreateTransactionDTO::fromRequest($request->validated(), $businessId, $branchId, $userId, $shiftId)
        );
        
        return $this->created(new TransactionResource($transaction), 'Transaksi berhasil disimpan.');
    }

    public function show(string $id): JsonResponse
    {
        $transaction = $this->service->findOrFail($id);
        return $this->success(new TransactionResource($transaction));
    }
}
```

---

## 12. Routes

### `app/Modules/Sales/Routes/api.php`

```php
<?php

use App\Modules\Sales\Controllers\PaymentMethodController;
use App\Modules\Sales\Controllers\ShiftController;
use App\Modules\Sales\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.scope', 'subscription.active'])->group(function () {
    
    // Payment Methods
    Route::apiResource('payment-methods', PaymentMethodController::class);
    
    // Shifts
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/',           [ShiftController::class, 'index'])->name('index');
        Route::get('/current',    [ShiftController::class, 'current'])->name('current');
        Route::post('/open',      [ShiftController::class, 'open'])->name('open');
        Route::post('/{id}/close', [ShiftController::class, 'close'])->name('close');
    });
    
    // Transactions
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store', 'show']);
    
});
```

---

## Checklist Module Sales

- [x] Migrations: `payment_methods`, `shifts`, `transactions`, `transaction_items`
- [x] Enums: `PaymentType`, `ShiftStatus`, `TransactionStatus`
- [x] Models: Setup casts, relasi, auto-generate invoice
- [x] Repositories: ShiftRepository (findActiveByUser), TransactionRepository (sumGrandTotalByShift)
- [x] DTOs & Requests: CreateShift, CloseShift, CreateTransaction (validasi nested array items)
- [x] Resources: ShiftResource, TransactionResource (with mapped items)
- [x] Services: 
  - `ShiftService`: Cek shift belum ditutup, hitung selisih kas aktual vs ekspektasi (mengambil total dari `sumGrandTotalByShift`).
  - `TransactionService`: Kalkulasi subtotal, tax, grand_total, change, dan **Integrasi Pemotongan Stok**. Memanggil `StockMovementService::processMovement` untuk tipe `Sale`, sehingga validasi stok minus tertangani.
- [x] Controllers: Wajib memiliki X-Branch-ID atau harus memiliki shift aktif jika `require_shift = true`.
- [x] Routes: Open/Close Shift, List/Create Transaction.

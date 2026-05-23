# VELORA — Module Sales (Part 2)
## Repositories · DTOs · Form Requests · Resources

---

## 4. Repository Interfaces

### `app/Modules/Sales/Repositories/Contracts/PaymentMethodRepositoryInterface.php`

```php
<?php

namespace App\Modules\Sales\Repositories\Contracts;

use App\Modules\Sales\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentMethodRepositoryInterface
{
    public function findById(string $id): ?PaymentMethod;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function allActive(string $businessId): Collection;
    public function create(array $data): PaymentMethod;
    public function update(string $id, array $data): PaymentMethod;
    public function delete(string $id): bool;
}
```

### `app/Modules/Sales/Repositories/Contracts/ShiftRepositoryInterface.php`

```php
<?php

namespace App\Modules\Sales\Repositories\Contracts;

use App\Modules\Sales\Models\Shift;
use Illuminate\Pagination\LengthAwarePaginator;

interface ShiftRepositoryInterface
{
    public function findById(string $id): ?Shift;
    public function findActiveByUser(string $businessId, string $userId): ?Shift;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Shift;
    public function update(string $id, array $data): Shift;
}
```

### `app/Modules/Sales/Repositories/Contracts/TransactionRepositoryInterface.php`

```php
<?php

namespace App\Modules\Sales\Repositories\Contracts;

use App\Modules\Sales\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function findById(string $id): ?Transaction;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function countByShift(string $shiftId): int;
    public function sumGrandTotalByShift(string $shiftId): int;
    public function create(array $data): Transaction;
    public function update(string $id, array $data): Transaction;
    
    // Items
    public function createItem(array $data): \App\Modules\Sales\Models\TransactionItem;
}
```

---

## 5. Repository Implementations

### `app/Modules/Sales/Repositories/Eloquent/PaymentMethodRepository.php`

```php
<?php

namespace App\Modules\Sales\Repositories\Eloquent;

use App\Modules\Sales\Models\PaymentMethod;
use App\Modules\Sales\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentMethodRepository extends BaseRepository implements PaymentMethodRepositoryInterface
{
    public function __construct(PaymentMethod $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?PaymentMethod
    {
        return PaymentMethod::find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return PaymentMethod::where('business_id', $businessId)
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('sort_order')
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function allActive(string $businessId): Collection
    {
        return PaymentMethod::where('business_id', $businessId)
            ->active()
            ->get();
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::create($data);
    }

    public function update(string $id, array $data): PaymentMethod
    {
        $pm = PaymentMethod::findOrFail($id);
        $pm->update($data);
        return $pm->fresh();
    }

    public function delete(string $id): bool
    {
        return PaymentMethod::findOrFail($id)->delete();
    }
}
```

### `app/Modules/Sales/Repositories/Eloquent/ShiftRepository.php`

```php
<?php

namespace App\Modules\Sales\Repositories\Eloquent;

use App\Modules\Sales\Enums\ShiftStatus;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Repositories\Contracts\ShiftRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ShiftRepository extends BaseRepository implements ShiftRepositoryInterface
{
    public function __construct(Shift $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?Shift
    {
        return Shift::with(['branch', 'user'])->find($id);
    }

    public function findActiveByUser(string $businessId, string $userId): ?Shift
    {
        return Shift::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('status', ShiftStatus::Open->value)
            ->first();
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return Shift::where('business_id', $businessId)
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->with(['branch', 'user'])
            ->latest('opened_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Shift
    {
        return Shift::create($data);
    }

    public function update(string $id, array $data): Shift
    {
        $shift = Shift::findOrFail($id);
        $shift->update($data);
        return $shift->fresh(['branch', 'user']);
    }
}
```

### `app/Modules/Sales/Repositories/Eloquent/TransactionRepository.php`

```php
<?php

namespace App\Modules\Sales\Repositories\Eloquent;

use App\Modules\Sales\Models\Transaction;
use App\Modules\Sales\Models\TransactionItem;
use App\Modules\Sales\Repositories\Contracts\TransactionRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository extends BaseRepository implements TransactionRepositoryInterface
{
    public function __construct(Transaction $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?Transaction
    {
        return Transaction::with([
            'branch', 'user', 'shift', 'paymentMethod', 
            'items.product', 'items.variant'
        ])->find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return Transaction::where('business_id', $businessId)
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->when($filters['shift_id'] ?? null, fn($q, $v) => $q->where('shift_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['payment_status'] ?? null, fn($q, $v) => $q->where('payment_status', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('invoice_number', 'like', "%{$v}%"))
            ->with(['branch', 'user', 'paymentMethod'])
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }
    
    public function countByShift(string $shiftId): int
    {
        return Transaction::where('shift_id', $shiftId)
            ->where('status', 'completed')
            ->count();
    }
    
    public function sumGrandTotalByShift(string $shiftId): int
    {
        return (int) Transaction::where('shift_id', $shiftId)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('amount_paid'); 
            // Note: Untuk penghitungan shift sederhana (cash drawer), 
            // idealnya difilter berdasarkan payment method "Cash".
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function update(string $id, array $data): Transaction
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function createItem(array $data): TransactionItem
    {
        return TransactionItem::create($data);
    }
}
```

---

## 6. Register Repository Bindings

```php
// Tambahkan ke app/Providers/RepositoryServiceProvider.php

use App\Modules\Sales\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Modules\Sales\Repositories\Eloquent\PaymentMethodRepository;
use App\Modules\Sales\Repositories\Contracts\ShiftRepositoryInterface;
use App\Modules\Sales\Repositories\Eloquent\ShiftRepository;
use App\Modules\Sales\Repositories\Contracts\TransactionRepositoryInterface;
use App\Modules\Sales\Repositories\Eloquent\TransactionRepository;

$this->app->bind(PaymentMethodRepositoryInterface::class, PaymentMethodRepository::class);
$this->app->bind(ShiftRepositoryInterface::class, ShiftRepository::class);
$this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
```

---

## 7. DTOs

### `app/Modules/Sales/DTOs/CreateShiftDTO.php`

```php
<?php

namespace App\Modules\Sales\DTOs;

class CreateShiftDTO
{
    public function __construct(
        public readonly string $businessId,
        public readonly string $branchId,
        public readonly string $userId,
        public readonly int    $startingCash = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(array $data, string $businessId, string $userId): static
    {
        return new static(
            businessId:   $businessId,
            branchId:     $data['branch_id'],
            userId:       $userId,
            startingCash: $data['starting_cash'] ?? 0,
            notes:        $data['notes'] ?? null,
        );
    }
}
```

### `app/Modules/Sales/DTOs/CloseShiftDTO.php`

```php
<?php

namespace App\Modules\Sales\DTOs;

class CloseShiftDTO
{
    public function __construct(
        public readonly int $actualEndingCash,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(array $data): static
    {
        return new static(
            actualEndingCash: $data['actual_ending_cash'],
            notes:            $data['notes'] ?? null,
        );
    }
}
```

### `app/Modules/Sales/DTOs/CreateTransactionDTO.php`

```php
<?php

namespace App\Modules\Sales\DTOs;

class CreateTransactionDTO
{
    public function __construct(
        public readonly string  $businessId,
        public readonly string  $branchId,
        public readonly string  $userId,
        public readonly ?string $shiftId = null,
        public readonly ?string $paymentMethodId = null,
        public readonly ?string $customerId = null,
        
        public readonly string  $orderType = 'dine_in',
        public readonly int     $discountAmount = 0,
        public readonly int     $taxAmount = 0,
        public readonly int     $serviceCharge = 0,
        
        public readonly int     $amountPaid = 0,
        public readonly ?string $paymentReference = null,
        public readonly ?string $notes = null,
        
        public readonly array   $items = [],
    ) {}

    public static function fromRequest(array $data, string $businessId, string $branchId, string $userId, ?string $shiftId): static
    {
        return new static(
            businessId:       $businessId,
            branchId:         $branchId,
            userId:           $userId,
            shiftId:          $shiftId,
            paymentMethodId:  $data['payment_method_id'] ?? null,
            customerId:       $data['customer_id'] ?? null,
            
            orderType:        $data['order_type'] ?? 'dine_in',
            discountAmount:   $data['discount_amount'] ?? 0,
            taxAmount:        $data['tax_amount'] ?? 0,
            serviceCharge:    $data['service_charge'] ?? 0,
            
            amountPaid:       $data['amount_paid'] ?? 0,
            paymentReference: $data['payment_reference'] ?? null,
            notes:            $data['notes'] ?? null,
            
            items:            $data['items'] ?? [],
        );
    }
}
```

---

## 8. Form Requests

### `app/Modules/Sales/Requests/StorePaymentMethodRequest.php`

```php
<?php

namespace App\Modules\Sales\Requests;

use App\Modules\Sales\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:50'],
            'type'           => ['required', new Enum(PaymentType::class)],
            'provider'       => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'is_active'      => ['nullable', 'boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

### `app/Modules/Sales/Requests/OpenShiftRequest.php`

```php
<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenShiftRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $businessId = auth()->user()->business_id;
        
        return [
            'branch_id'     => ['required', 'uuid', Rule::exists('branches', 'id')->where('business_id', $businessId)],
            'starting_cash' => ['nullable', 'integer', 'min:0'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
```

### `app/Modules/Sales/Requests/CloseShiftRequest.php`

```php
<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseShiftRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'actual_ending_cash' => ['required', 'integer', 'min:0'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
```

### `app/Modules/Sales/Requests/StoreTransactionRequest.php`

```php
<?php

namespace App\Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $businessId = auth()->user()->business_id;

        return [
            'payment_method_id' => ['required', 'uuid', Rule::exists('payment_methods', 'id')->where('business_id', $businessId)],
            'customer_id'       => ['nullable', 'uuid'],
            
            'order_type'        => ['required', 'string', 'in:dine_in,take_away,delivery'],
            'discount_amount'   => ['nullable', 'integer', 'min:0'],
            'tax_amount'        => ['nullable', 'integer', 'min:0'],
            'service_charge'    => ['nullable', 'integer', 'min:0'],
            
            'amount_paid'       => ['required', 'integer', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string'],
            
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['required', 'uuid', Rule::exists('products', 'id')->where('business_id', $businessId)],
            'items.*.variant_id'    => ['nullable', 'uuid', Rule::exists('product_variants', 'id')->where('business_id', $businessId)],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.discount_amount'=> ['nullable', 'integer', 'min:0'],
            'items.*.notes'         => ['nullable', 'string'],
        ];
    }
}
```

---

## 9. Resources

### `app/Modules/Sales/Resources/ShiftResource.php`

```php
<?php

namespace App\Modules\Sales\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'status'               => $this->status,
            'status_label'         => $this->status->label(),
            
            'opened_at'            => $this->opened_at?->toIso8601String(),
            'closed_at'            => $this->closed_at?->toIso8601String(),
            
            'starting_cash'        => $this->starting_cash,
            'actual_ending_cash'   => $this->actual_ending_cash,
            'expected_ending_cash' => $this->expected_ending_cash,
            'difference'           => $this->difference,
            
            'notes'                => $this->notes,
            
            'branch'               => $this->whenLoaded('branch', fn() => ['id' => $this->branch->id, 'name' => $this->branch->name]),
            'user'                 => $this->whenLoaded('user', fn() => ['id' => $this->user->id, 'name' => $this->user->name]),
        ];
    }
}
```

### `app/Modules/Sales/Resources/TransactionResource.php`

```php
<?php

namespace App\Modules\Sales\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'invoice_number'    => $this->invoice_number,
            'status'            => $this->status,
            'status_label'      => $this->status->label(),
            'payment_status'    => $this->payment_status,
            'order_type'        => $this->order_type,
            
            'totals' => [
                'subtotal'        => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'tax_amount'      => $this->tax_amount,
                'service_charge'  => $this->service_charge,
                'grand_total'     => $this->grand_total,
                'amount_paid'     => $this->amount_paid,
                'change_amount'   => $this->change_amount,
                'currency'        => $this->currency,
            ],
            
            'payment_method'    => $this->whenLoaded('paymentMethod', fn() => [
                'id'   => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
                'type' => $this->paymentMethod->type,
            ]),
            
            'user'              => $this->whenLoaded('user', fn() => ['id' => $this->user->id, 'name' => $this->user->name]),
            'shift_id'          => $this->shift_id,
            
            'notes'             => $this->notes,
            
            'items' => $this->whenLoaded('items', function() {
                return $this->items->map(fn($item) => [
                    'id'              => $item->id,
                    'product_id'      => $item->product_id,
                    'variant_id'      => $item->product_variant_id,
                    'product_name'    => $item->product_name,
                    'variant_name'    => $item->variant_name,
                    'sku'             => $item->sku,
                    'quantity'        => $item->quantity,
                    'unit_price'      => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_amount'      => $item->tax_amount,
                    'subtotal'        => $item->subtotal,
                    'notes'           => $item->notes,
                ]);
            }),
            
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
```

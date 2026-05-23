# VELORA — Module Finance (Part 2)
## Repositories · DTOs · Form Requests · Resources · Services · Controllers

---

## 3. Repositories

### `app/Modules/Finance/Repositories/Contracts/ExpenseCategoryRepositoryInterface.php`
```php
<?php

namespace App\Modules\Finance\Repositories\Contracts;

use App\Modules\Finance\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseCategoryRepositoryInterface
{
    public function findById(string $id): ?ExpenseCategory;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function allActive(string $businessId): Collection;
    public function create(array $data): ExpenseCategory;
    public function update(string $id, array $data): ExpenseCategory;
    public function delete(string $id): bool;
}
```

### `app/Modules/Finance/Repositories/Contracts/ExpenseRepositoryInterface.php`
```php
<?php

namespace App\Modules\Finance\Repositories\Contracts;

use App\Modules\Finance\Models\Expense;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseRepositoryInterface
{
    public function findById(string $id): ?Expense;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Expense;
    public function update(string $id, array $data): Expense;
    public function delete(string $id): bool;
}
```

### `app/Modules/Finance/Repositories/Eloquent/ExpenseCategoryRepository.php`
```php
<?php

namespace App\Modules\Finance\Repositories\Eloquent;

use App\Modules\Finance\Models\ExpenseCategory;
use App\Modules\Finance\Repositories\Contracts\ExpenseCategoryRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseCategoryRepository extends BaseRepository implements ExpenseCategoryRepositoryInterface
{
    public function __construct(ExpenseCategory $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?ExpenseCategory
    {
        return ExpenseCategory::find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return ExpenseCategory::where('business_id', $businessId)
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('sort_order')
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function allActive(string $businessId): Collection
    {
        return ExpenseCategory::where('business_id', $businessId)->active()->orderBy('sort_order')->get();
    }

    public function create(array $data): ExpenseCategory
    {
        return ExpenseCategory::create($data);
    }

    public function update(string $id, array $data): ExpenseCategory
    {
        $category = ExpenseCategory::findOrFail($id);
        $category->update($data);
        return $category->fresh();
    }

    public function delete(string $id): bool
    {
        return ExpenseCategory::findOrFail($id)->delete();
    }
}
```

### `app/Modules/Finance/Repositories/Eloquent/ExpenseRepository.php`
```php
<?php

namespace App\Modules\Finance\Repositories\Eloquent;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseRepository extends BaseRepository implements ExpenseRepositoryInterface
{
    public function __construct(Expense $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?Expense
    {
        return Expense::with(['branch', 'category', 'user'])->find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return Expense::where('business_id', $businessId)
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('expense_category_id', $v))
            ->when($filters['start_date'] ?? null, fn($q, $v) => $q->whereDate('expense_date', '>=', $v))
            ->when($filters['end_date'] ?? null, fn($q, $v) => $q->whereDate('expense_date', '<=', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => 
                $q->where(fn($q) => 
                    $q->where('title', 'like', "%{$v}%")
                      ->orWhere('reference_number', 'like', "%{$v}%")
                )
            )
            ->with(['branch', 'category', 'user'])
            ->latest('expense_date')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function update(string $id, array $data): Expense
    {
        $expense = Expense::findOrFail($id);
        $expense->update($data);
        return $expense->fresh(['branch', 'category', 'user']);
    }

    public function delete(string $id): bool
    {
        return Expense::findOrFail($id)->delete();
    }
}
```

### Register Bindings
```php
// app/Providers/RepositoryServiceProvider.php
use App\Modules\Finance\Repositories\Contracts\ExpenseCategoryRepositoryInterface;
use App\Modules\Finance\Repositories\Eloquent\ExpenseCategoryRepository;
use App\Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Modules\Finance\Repositories\Eloquent\ExpenseRepository;

$this->app->bind(ExpenseCategoryRepositoryInterface::class, ExpenseCategoryRepository::class);
$this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);
```

---

## 4. DTOs & Form Requests

### `app/Modules/Finance/DTOs/CreateExpenseDTO.php`
```php
<?php

namespace App\Modules\Finance\DTOs;

class CreateExpenseDTO
{
    public function __construct(
        public readonly string  $businessId,
        public readonly string  $branchId,
        public readonly string  $userId,
        public readonly string  $title,
        public readonly int     $amount,
        public readonly string  $expenseDate,
        public readonly ?string $expenseCategoryId = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(array $data, string $businessId, string $branchId, string $userId): static
    {
        return new static(
            businessId:        $businessId,
            branchId:          $branchId,
            userId:            $userId,
            title:             $data['title'],
            amount:            $data['amount'],
            expenseDate:       $data['expense_date'],
            expenseCategoryId: $data['expense_category_id'] ?? null,
            description:       $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'business_id'         => $this->businessId,
            'branch_id'           => $this->branchId,
            'user_id'             => $this->userId,
            'title'               => $this->title,
            'amount'              => $this->amount,
            'expense_date'        => $this->expenseDate,
            'expense_category_id' => $this->expenseCategoryId,
            'description'         => $this->description,
        ];
    }
}
```

### `app/Modules/Finance/Requests/StoreExpenseRequest.php`
```php
<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $businessId = auth()->user()->business_id;

        return [
            'title'               => ['required', 'string', 'max:150'],
            'amount'              => ['required', 'integer', 'min:1'],
            'expense_date'        => ['required', 'date'],
            'expense_category_id' => ['nullable', 'uuid', Rule::exists('expense_categories', 'id')->where('business_id', $businessId)],
            'description'         => ['nullable', 'string'],
            'attachment'          => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}
```

---

## 5. Services

### `app/Modules/Finance/Services/ExpenseService.php`
```php
<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\DTOs\CreateExpenseDTO;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Shared\Exceptions\VeloraException;
use App\Shared\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ExpenseService extends BaseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepo
    ) {}

    public function getAll(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return $this->expenseRepo->findByBusiness($businessId, $filters);
    }

    public function findOrFail(string $id): Expense
    {
        $expense = $this->expenseRepo->findById($id);

        if (!$expense || $expense->business_id !== app('current.business_id')) {
            throw VeloraException::notFound('Expense');
        }

        return $expense;
    }

    public function create(CreateExpenseDTO $dto, ?\Illuminate\Http\UploadedFile $file = null): Expense
    {
        $data = $dto->toArray();
        
        $expense = $this->expenseRepo->create($data);
        
        if ($file) {
            $path = $file->store("businesses/{$dto->businessId}/expenses/{$expense->id}", 'public');
            $this->expenseRepo->update($expense->id, ['attachment' => $path]);
        }

        return $expense->fresh(['branch', 'category', 'user']);
    }

    public function delete(string $id): void
    {
        $expense = $this->findOrFail($id);
        
        if ($expense->attachment) {
            Storage::disk('public')->delete($expense->attachment);
        }
        
        $this->expenseRepo->delete($expense->id);
    }
}
```

---

## 6. Controllers & Routes

### `app/Modules/Finance/Controllers/ExpenseController.php`
```php
<?php

namespace App\Modules\Finance\Controllers;

use App\Modules\Finance\DTOs\CreateExpenseDTO;
use App\Modules\Finance\Requests\StoreExpenseRequest;
use App\Modules\Finance\Resources\ExpenseResource;
use App\Modules\Finance\Services\ExpenseService;
use App\Shared\Controllers\BaseController;
use App\Shared\Exceptions\VeloraException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends BaseController
{
    public function __construct(private readonly ExpenseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $paginator  = $this->service->getAll($businessId, $request->all());
        
        return $this->paginated($paginator, ExpenseResource::collection($paginator));
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $userId     = $request->user()->id;
        $branchId   = $request->header('X-Branch-ID');
        
        if (!$branchId) {
            throw new VeloraException('Header X-Branch-ID wajib disertakan saat mencatat pengeluaran.', 400);
        }

        $expense = $this->service->create(
            CreateExpenseDTO::fromRequest($request->validated(), $businessId, $branchId, $userId),
            $request->file('attachment')
        );
        
        return $this->created(new ExpenseResource($expense), 'Pengeluaran berhasil dicatat.');
    }

    public function show(string $id): JsonResponse
    {
        $expense = $this->service->findOrFail($id);
        return $this->success(new ExpenseResource($expense));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->noContent();
    }
}
```

### `app/Modules/Finance/Routes/api.php`
```php
<?php

use App\Modules\Finance\Controllers\ExpenseCategoryController;
use App\Modules\Finance\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.scope', 'subscription.active'])->group(function () {
    Route::apiResource('expense-categories', ExpenseCategoryController::class);
    Route::apiResource('expenses', ExpenseController::class)->except(['update']);
});
```

# VELORA — Module CRM (Part 2)
## Repositories · DTOs · Form Requests · Resources · Services · Controllers

---

## 4. Repositories

### `app/Modules/CRM/Repositories/Contracts/CustomerGroupRepositoryInterface.php`
```php
<?php

namespace App\Modules\CRM\Repositories\Contracts;

use App\Modules\CRM\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerGroupRepositoryInterface
{
    public function findById(string $id): ?CustomerGroup;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function allActive(string $businessId): Collection;
    public function create(array $data): CustomerGroup;
    public function update(string $id, array $data): CustomerGroup;
    public function delete(string $id): bool;
}
```

### `app/Modules/CRM/Repositories/Contracts/CustomerRepositoryInterface.php`
```php
<?php

namespace App\Modules\CRM\Repositories\Contracts;

use App\Modules\CRM\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function findById(string $id): ?Customer;
    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator;
    public function search(string $businessId, string $query): LengthAwarePaginator;
    public function countByBusiness(string $businessId): int;
    public function create(array $data): Customer;
    public function update(string $id, array $data): Customer;
    public function delete(string $id): bool;
}
```

### `app/Modules/CRM/Repositories/Eloquent/CustomerGroupRepository.php`
```php
<?php

namespace App\Modules\CRM\Repositories\Eloquent;

use App\Modules\CRM\Models\CustomerGroup;
use App\Modules\CRM\Repositories\Contracts\CustomerGroupRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerGroupRepository extends BaseRepository implements CustomerGroupRepositoryInterface
{
    public function __construct(CustomerGroup $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?CustomerGroup
    {
        return CustomerGroup::find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return CustomerGroup::where('business_id', $businessId)
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function allActive(string $businessId): Collection
    {
        return CustomerGroup::where('business_id', $businessId)->active()->get();
    }

    public function create(array $data): CustomerGroup
    {
        return CustomerGroup::create($data);
    }

    public function update(string $id, array $data): CustomerGroup
    {
        $group = CustomerGroup::findOrFail($id);
        $group->update($data);
        return $group->fresh();
    }

    public function delete(string $id): bool
    {
        return CustomerGroup::findOrFail($id)->delete();
    }
}
```

### `app/Modules/CRM/Repositories/Eloquent/CustomerRepository.php`
```php
<?php

namespace App\Modules\CRM\Repositories\Eloquent;

use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Repositories\Contracts\CustomerRepositoryInterface;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findById(string $id): ?Customer
    {
        return Customer::with('group')->find($id);
    }

    public function findByBusiness(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return Customer::where('business_id', $businessId)
            ->when($filters['group_id'] ?? null, fn($q, $v) => $q->where('customer_group_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => 
                $q->where(fn($q) => 
                    $q->where('name', 'like', "%{$v}%")
                      ->orWhere('phone', 'like', "%{$v}%")
                      ->orWhere('email', 'like', "%{$v}%")
                )
            )
            ->with('group')
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function search(string $businessId, string $query): LengthAwarePaginator
    {
        return Customer::where('business_id', $businessId)
            ->active()
            ->where(fn($q) => 
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
            )
            ->with('group')
            ->paginate(15);
    }
    
    public function countByBusiness(string $businessId): int
    {
        return Customer::where('business_id', $businessId)->count();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(string $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->update($data);
        return $customer->fresh(['group']);
    }

    public function delete(string $id): bool
    {
        return Customer::findOrFail($id)->delete();
    }
}
```

### Register Bindings
```php
// app/Providers/RepositoryServiceProvider.php
use App\Modules\CRM\Repositories\Contracts\CustomerGroupRepositoryInterface;
use App\Modules\CRM\Repositories\Eloquent\CustomerGroupRepository;
use App\Modules\CRM\Repositories\Contracts\CustomerRepositoryInterface;
use App\Modules\CRM\Repositories\Eloquent\CustomerRepository;

$this->app->bind(CustomerGroupRepositoryInterface::class, CustomerGroupRepository::class);
$this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
```

---

## 5. DTOs & Form Requests

### `app/Modules/CRM/DTOs/CreateCustomerDTO.php`
```php
<?php

namespace App\Modules\CRM\DTOs;

class CreateCustomerDTO
{
    public function __construct(
        public readonly string  $businessId,
        public readonly string  $name,
        public readonly ?string $customerGroupId = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $province = null,
        public readonly ?string $birthDate = null,
        public readonly string  $status = 'active',
    ) {}

    public static function fromRequest(array $data, string $businessId): static
    {
        return new static(
            businessId:      $businessId,
            name:            $data['name'],
            customerGroupId: $data['customer_group_id'] ?? null,
            email:           $data['email'] ?? null,
            phone:           $data['phone'] ?? null,
            address:         $data['address'] ?? null,
            city:            $data['city'] ?? null,
            province:        $data['province'] ?? null,
            birthDate:       $data['birth_date'] ?? null,
            status:          $data['status'] ?? 'active',
        );
    }

    public function toArray(): array
    {
        return [
            'business_id'       => $this->businessId,
            'name'              => $this->name,
            'customer_group_id' => $this->customerGroupId,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'address'           => $this->address,
            'city'              => $this->city,
            'province'          => $this->province,
            'birth_date'        => $this->birthDate,
            'status'            => $this->status,
        ];
    }
}
```

### `app/Modules/CRM/Requests/StoreCustomerRequest.php`
```php
<?php

namespace App\Modules\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $businessId = auth()->user()->business_id;

        return [
            'name'              => ['required', 'string', 'max:150'],
            'customer_group_id' => ['nullable', 'uuid', Rule::exists('customer_groups', 'id')->where('business_id', $businessId)],
            'email'             => ['nullable', 'email', Rule::unique('customers')->where('business_id', $businessId)],
            'phone'             => ['nullable', 'string', 'max:20', Rule::unique('customers')->where('business_id', $businessId)],
            'address'           => ['nullable', 'string'],
            'city'              => ['nullable', 'string', 'max:100'],
            'province'          => ['nullable', 'string', 'max:100'],
            'birth_date'        => ['nullable', 'date'],
            'status'            => ['nullable', 'in:active,inactive'],
        ];
    }
}
```

---

## 6. Services

### `app/Modules/CRM/Services/CustomerService.php`
```php
<?php

namespace App\Modules\CRM\Services;

use App\Modules\CRM\DTOs\CreateCustomerDTO;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Repositories\Contracts\CustomerRepositoryInterface;
use App\Modules\Subscription\Services\SubscriptionService;
use App\Shared\Exceptions\VeloraException;
use App\Shared\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService extends BaseService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepo,
        private readonly SubscriptionService         $subscriptionService
    ) {}

    public function getAll(string $businessId, array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepo->findByBusiness($businessId, $filters);
    }

    public function search(string $businessId, string $query): LengthAwarePaginator
    {
        return $this->customerRepo->search($businessId, $query);
    }

    public function findOrFail(string $id): Customer
    {
        $customer = $this->customerRepo->findById($id);

        if (!$customer || $customer->business_id !== app('current.business_id')) {
            throw VeloraException::notFound('Customer');
        }

        return $customer;
    }

    public function create(CreateCustomerDTO $dto): Customer
    {
        $currentCount = $this->customerRepo->countByBusiness($dto->businessId);
        
        if (!$this->subscriptionService->checkLimit($dto->businessId, 'max_customers', $currentCount)) {
            throw new VeloraException(
                'Batas maksimal pelanggan telah tercapai. Upgrade paket untuk menambah pelanggan.',
                403,
                'SUBSCRIPTION_LIMIT_REACHED'
            );
        }

        return $this->customerRepo->create($dto->toArray());
    }

    public function update(string $id, array $data): Customer
    {
        $customer = $this->findOrFail($id);
        
        // Handle uniqueness if email/phone changed
        // ... (can add custom checking here if needed, usually handled by FormRequest)

        return $this->customerRepo->update($customer->id, $data);
    }

    public function delete(string $id): void
    {
        $customer = $this->findOrFail($id);
        $this->customerRepo->delete($customer->id);
    }
}
```

---

## 7. Controllers & Routes

### `app/Modules/CRM/Controllers/CustomerController.php`
```php
<?php

namespace App\Modules\CRM\Controllers;

use App\Modules\CRM\DTOs\CreateCustomerDTO;
use App\Modules\CRM\Requests\StoreCustomerRequest;
use App\Modules\CRM\Resources\CustomerResource;
use App\Modules\CRM\Services\CustomerService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends BaseController
{
    public function __construct(private readonly CustomerService $service) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $paginator  = $this->service->getAll($businessId, $request->all());
        
        return $this->paginated($paginator, CustomerResource::collection($paginator));
    }

    public function search(Request $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $query = $request->query('q', '');
        
        if (empty($query)) return $this->success([]);

        $results = $this->service->search($businessId, $query);
        return $this->paginated($results, CustomerResource::collection($results));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $businessId = app('current.business_id');
        $customer = $this->service->create(
            CreateCustomerDTO::fromRequest($request->validated(), $businessId)
        );
        
        return $this->created(new CustomerResource($customer), 'Pelanggan berhasil ditambahkan.');
    }

    public function show(string $id): JsonResponse
    {
        $customer = $this->service->findOrFail($id);
        return $this->success(new CustomerResource($customer));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $businessId = app('current.business_id');
        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:150'],
            'customer_group_id' => ['nullable', 'uuid'],
            'email'             => ['nullable', 'email', Rule::unique('customers')->where('business_id', $businessId)->ignore($id)],
            'phone'             => ['nullable', 'string', 'max:20', Rule::unique('customers')->where('business_id', $businessId)->ignore($id)],
            'status'            => ['sometimes', 'in:active,inactive'],
        ]);
        
        $customer = $this->service->update($id, $data);
        return $this->success(new CustomerResource($customer), 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->noContent();
    }
}
```

### `app/Modules/CRM/Routes/api.php`
```php
<?php

use App\Modules\CRM\Controllers\CustomerController;
use App\Modules\CRM\Controllers\CustomerGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.scope', 'subscription.active'])->group(function () {
    Route::apiResource('customer-groups', CustomerGroupController::class);
    
    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::apiResource('customers', CustomerController::class);
});
```

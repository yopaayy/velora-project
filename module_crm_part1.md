# VELORA — Module CRM (Part 1)
## Migrations · Enums · Models

---

## Folder Structure

```
app/Modules/CRM/
├── Controllers/
│   ├── CustomerGroupController.php
│   └── CustomerController.php
├── DTOs/
│   └── CreateCustomerDTO.php
├── Enums/
│   └── CustomerStatus.php
├── Models/
│   ├── CustomerGroup.php
│   └── Customer.php
├── Repositories/
│   ├── Contracts/
│   │   ├── CustomerGroupRepositoryInterface.php
│   │   └── CustomerRepositoryInterface.php
│   └── Eloquent/
│       ├── CustomerGroupRepository.php
│       └── CustomerRepository.php
├── Requests/
│   ├── StoreCustomerGroupRequest.php
│   └── StoreCustomerRequest.php
├── Resources/
│   ├── CustomerGroupResource.php
│   └── CustomerResource.php
├── Services/
│   ├── CustomerGroupService.php
│   └── CustomerService.php
└── Routes/
    └── api.php
```

---

## 1. Migrations

### Customer Groups Table

```php
<?php
// database/migrations/crm/2026_05_19_000050_create_customer_groups_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            $table->string('name', 50);          // Regular, VIP, Wholesale
            $table->integer('discount_percent')->default(0)->comment('Diskon default grup (0-100)');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('customer_groups'); }
};
```

### Customers Table

```php
<?php
// database/migrations/crm/2026_05_19_000051_create_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('customer_group_id')->nullable();

            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            
            $table->date('birth_date')->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive');
            
            // Loyalty & Stats
            $table->integer('loyalty_points')->default(0);
            $table->bigInteger('total_spent')->default(0);
            $table->integer('total_visits')->default(0);
            $table->timestamp('last_visit_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->nullOnDelete();

            $table->unique(['business_id', 'email']);
            $table->unique(['business_id', 'phone']);
            $table->index(['business_id', 'status']);
            $table->fullText(['name', 'phone', 'email'], 'customers_search');
        });
    }

    public function down(): void { Schema::dropIfExists('customers'); }
};
```

---

## 2. Enums

### `app/Modules/CRM/Enums/CustomerStatus.php`

```php
<?php

namespace App\Modules\CRM\Enums;

enum CustomerStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Aktif',
            self::Inactive => 'Tidak Aktif',
        };
    }
}
```

---

## 3. Models

### `app/Modules/CRM/Models/CustomerGroup.php`

```php
<?php

namespace App\Modules\CRM\Models;

use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerGroup extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'name', 'discount_percent', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'is_active'        => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function customers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Customer::class, 'customer_group_id');
    }
}
```

### `app/Modules/CRM/Models/Customer.php`

```php
<?php

namespace App\Modules\CRM\Models;

use App\Modules\CRM\Enums\CustomerStatus;
use App\Modules\Sales\Models\Transaction;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'customer_group_id',
        'name', 'email', 'phone', 'address', 'city', 'province',
        'birth_date', 'status',
        'loyalty_points', 'total_spent', 'total_visits', 'last_visit_at',
    ];

    protected function casts(): array
    {
        return [
            'status'         => CustomerStatus::class,
            'birth_date'     => 'date',
            'last_visit_at'  => 'datetime',
            'loyalty_points' => 'integer',
            'total_spent'    => 'integer',
            'total_visits'   => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }
    
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', CustomerStatus::Active->value);
    }
    
    public function addPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
    }
}
```

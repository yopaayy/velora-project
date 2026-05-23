# VELORA — Module Finance (Part 1)
## Migrations · Enums · Models

---

## Folder Structure

```
app/Modules/Finance/
├── Controllers/
│   ├── ExpenseCategoryController.php
│   └── ExpenseController.php
├── DTOs/
│   └── CreateExpenseDTO.php
├── Models/
│   ├── ExpenseCategory.php
│   └── Expense.php
├── Repositories/
│   ├── Contracts/
│   │   ├── ExpenseCategoryRepositoryInterface.php
│   │   └── ExpenseRepositoryInterface.php
│   └── Eloquent/
│       ├── ExpenseCategoryRepository.php
│       └── ExpenseRepository.php
├── Requests/
│   ├── StoreExpenseCategoryRequest.php
│   └── StoreExpenseRequest.php
├── Resources/
│   ├── ExpenseCategoryResource.php
│   └── ExpenseResource.php
├── Services/
│   ├── ExpenseCategoryService.php
│   └── ExpenseService.php
└── Routes/
    └── api.php
```

---

## 1. Migrations

### Expense Categories Table

```php
<?php
// database/migrations/finance/2026_05_19_000060_create_expense_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');

            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void { Schema::dropIfExists('expense_categories'); }
};
```

### Expenses Table

```php
<?php
// database/migrations/finance/2026_05_19_000061_create_expenses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id');
            $table->uuid('expense_category_id')->nullable();
            $table->uuid('user_id')->comment('Yang mencatat pengeluaran');

            $table->string('reference_number', 50)->unique();
            $table->date('expense_date');
            
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->bigInteger('amount')->default(0);
            
            $table->string('attachment')->nullable()->comment('Bukti struk / invoice');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['business_id', 'branch_id', 'expense_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('expenses'); }
};
```

---

## 2. Models

### `app/Modules/Finance/Models/ExpenseCategory.php`

```php
<?php

namespace App\Modules\Finance\Models;

use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'name', 'description', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }
}
```

### `app/Modules/Finance/Models/Expense.php`

```php
<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Tenant\Models\Branch;
use App\Shared\Helpers\CodeGenerator;
use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'id', 'business_id', 'branch_id', 'expense_category_id', 'user_id',
        'reference_number', 'expense_date', 'title', 'description',
        'amount', 'attachment',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount'       => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->reference_number)) {
                $model->reference_number = CodeGenerator::invoiceNumber('EXP');
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment
            ? \Illuminate\Support\Facades\Storage::url($this->attachment)
            : null;
    }
}
```

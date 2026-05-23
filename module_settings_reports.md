# VELORA — Module Settings & Reports
## API Keys · Settings · AI Dashboard Insights

---

## Bagian 1: Module Settings (API Keys & Configurations)

Modul ini menangani pembuatan kredensial API untuk akses pihak ketiga (misalnya aplikasi kasir desktop/mobile atau mesin kasir self-service) serta pengaturan sistem (Pajak, Format Struk, dll).

### Folder Structure
```
app/Modules/Settings/
├── Controllers/
│   ├── ApiKeyController.php
│   └── BusinessSettingController.php
├── Models/
│   └── ApiKey.php
└── Services/
    └── ApiKeyService.php
```

### 1. Migrations: API Keys

```php
<?php
// database/migrations/settings/2026_05_19_000070_create_api_keys_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            
            $table->string('name', 100);
            $table->string('token', 64)->unique();
            $table->json('permissions')->nullable(); // misal: ["pos:write", "inventory:read"]
            $table->timestamp('last_used_at')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void { Schema::dropIfExists('api_keys'); }
};
```

### 2. Model: `ApiKey`

```php
<?php

namespace App\Modules\Settings\Models;

use App\Shared\Traits\BelongsToBusiness;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $fillable = [
        'id', 'business_id', 'name', 'token', 'permissions',
        'last_used_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions'  => 'array',
            'last_used_at' => 'datetime',
            'is_active'    => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->token)) {
                $model->token = hash('sha256', Str::random(40));
            }
        });
    }
}
```

### 3. Controller: `BusinessSettingController`
*Catatan: Settings disimpan dalam format JSON di kolom `settings` pada tabel `businesses` (dibuat di Module Tenant).*

```php
<?php

namespace App\Modules\Settings\Controllers;

use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessSettingController extends BaseController
{
    public function show(): JsonResponse
    {
        $business = app('current.business');
        return $this->success($business->settings ?? []);
    }

    public function update(Request $request): JsonResponse
    {
        $business = app('current.business');
        
        $validated = $request->validate([
            'tax_rate'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive'         => ['nullable', 'boolean'],
            'receipt_header'        => ['nullable', 'string'],
            'receipt_footer'        => ['nullable', 'string'],
            'require_shift'         => ['nullable', 'boolean'],
            'allow_negative_stock'  => ['nullable', 'boolean'],
        ]);

        $currentSettings = $business->settings ?? [];
        $business->update([
            'settings' => array_merge($currentSettings, $validated)
        ]);

        return $this->success($business->fresh()->settings, 'Pengaturan bisnis berhasil diperbarui.');
    }
}
```

---

## Bagian 2: Module Reports & AI Insights

Modul ini bertanggung jawab untuk query berat ke database (Sales, Inventory, CRM) dan merangkumnya menjadi Insight yang bisa ditampilkan di Dashboard (atau dikirim ke LLM/AI untuk *conversational query*).

### Folder Structure
```
app/Modules/Reports/
├── Controllers/
│   ├── DashboardController.php
│   └── ReportController.php
└── Services/
    ├── DashboardService.php
    └── AIInsightService.php
```

### 1. Service: `DashboardService`
Digunakan untuk query metric dasar (Revenue, Transaksi, Produk terlaris).

```php
<?php

namespace App\Modules\Reports\Services;

use App\Modules\Sales\Models\Transaction;
use App\Modules\POS\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function getSummary(string $businessId, string $dateRange = 'today'): array
    {
        $query = Transaction::where('business_id', $businessId)->where('status', 'completed');
        
        if ($dateRange === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($dateRange === 'this_month') {
            $query->whereMonth('created_at', Carbon::now()->month);
        }

        $totalRevenue = (clone $query)->sum('grand_total');
        $totalSales   = (clone $query)->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_sales'   => $totalSales,
            'average_order' => $totalSales > 0 ? round($totalRevenue / $totalSales) : 0,
        ];
    }

    public function getTopProducts(string $businessId, int $limit = 5): array
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $businessId)
            ->where('transactions.status', 'completed')
            ->select('transaction_items.product_name', DB::raw('SUM(transaction_items.quantity) as total_sold'))
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
```

### 2. Service: `AIInsightService` (The AI Brain)
Membaca data historis kemudian memanggil Gemini/OpenAI API untuk memberikan Insight bisnis.

```php
<?php

namespace App\Modules\Reports\Services;

use App\Shared\Exceptions\VeloraException;
use Illuminate\Support\Facades\Http;

class AIInsightService
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function generateBusinessAdvice(string $businessId): string
    {
        $summary = $this->dashboardService->getSummary($businessId, 'this_month');
        $topProducts = $this->dashboardService->getTopProducts($businessId);

        $prompt = $this->buildPrompt($summary, $topProducts);

        return $this->callLLM($prompt);
    }

    private function buildPrompt(array $summary, array $topProducts): string
    {
        $productsText = collect($topProducts)
            ->map(fn($p) => "- {$p->product_name} ({$p->total_sold} terjual)")
            ->join("\n");

        return <<<PROMPT
        Anda adalah konsultan bisnis AI untuk platform VELORA POS.
        Berikut adalah data penjualan bisnis bulan ini:
        - Total Pendapatan: Rp {$summary['total_revenue']}
        - Total Transaksi: {$summary['total_sales']}
        - Rata-rata Pesanan: Rp {$summary['average_order']}
        
        Produk Terlaris:
        {$productsText}
        
        Berdasarkan data di atas, berikan 3 insight singkat dan rekomendasi strategi bisnis yang konkrit (maksimal 150 kata).
        PROMPT;
    }

    private function callLLM(string $prompt): string
    {
        $apiKey = env('GEMINI_API_KEY'); // Or OpenAI
        
        if (!$apiKey) {
            throw new VeloraException('AI API Key not configured.', 500);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        if ($response->failed()) {
            throw new VeloraException('Gagal menghubungi layanan AI.', 500);
        }

        return $response->json('candidates.0.content.parts.0.text') ?? 'Tidak ada insight yang dihasilkan.';
    }
}
```

### 3. Controller & Routes

```php
<?php
// app/Modules/Reports/Controllers/DashboardController.php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\Services\AIInsightService;
use App\Modules\Reports\Services\DashboardService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly AIInsightService $aiService
    ) {}

    public function summary(): JsonResponse
    {
        $businessId = app('current.business_id');
        
        $data = [
            'summary'      => $this->dashboardService->getSummary($businessId),
            'top_products' => $this->dashboardService->getTopProducts($businessId),
        ];

        return $this->success($data);
    }

    public function aiInsights(): JsonResponse
    {
        $businessId = app('current.business_id');
        
        // Memastikan Tenant berada di paket Enterprise (Mengecek limit Has AI)
        if (!app(\App\Modules\Subscription\Services\SubscriptionService::class)->canUseFeature($businessId, 'has_ai')) {
            return $this->error('Fitur AI Insights hanya tersedia pada paket Enterprise.', 403, 'FEATURE_NOT_AVAILABLE');
        }

        $advice = $this->aiService->generateBusinessAdvice($businessId);

        return $this->success(['advice' => $advice]);
    }
}
```

```php
// app/Modules/Reports/Routes/api.php

use App\Modules\Reports\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.scope', 'subscription.active'])->prefix('dashboard')->group(function () {
    Route::get('/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
    Route::get('/ai-insights', [DashboardController::class, 'aiInsights'])->middleware('feature.limit:has_ai')->name('dashboard.ai_insights');
});
```

---

## Kesimpulan Blueprint Arsitektur VELORA

1. **Clean Architecture Terjaga:** Pemisahan Models, Repositories, DTOs, Services, dan Controllers menjamin kode yang scalable.
2. **Tenant & Subscription Isolation:** Seluruh controller secara otomatis hanya bisa melihat data milik `business_id` terkait. Fitur dibatasi berdasarkan tier langganan.
3. **Advanced Integrations:** 
   - `Transaction` otomatis memotong stok lewat `StockMovement`.
   - Modul `Finance` terpisah dari POS, namun dapat dikonsolidasikan lewat laporan.
   - Modul `AI Insight` langsung membaca data *real-time* POS dan mengolahnya menjadi rekomendasi.

Dengan selesainya dokumen konfigurasi, laporan, dan AI ini, **Fase Desain Blueprint Arsitektur Backend telah 100% selesai.**

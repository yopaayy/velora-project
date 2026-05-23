<?php
namespace App\Modules\POS\Services;

use App\Modules\POS\DTOs\CreateProductDTO;
use App\Modules\POS\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    public function createProduct(CreateProductDTO $dto): Product
    {
        // Simple SKU generator if missing
        $sku = $dto->sku ?? ('SKU-' . strtoupper(Str::random(6)));

        return Product::create([
            'business_id' => $dto->business_id,
            'name' => $dto->name,
            'cost_price' => $dto->cost_price,
            'selling_price' => $dto->selling_price,
            'stock_quantity' => $dto->stock_quantity,
            'category_id' => $dto->category_id,
            'sku' => $sku,
            'barcode' => $dto->barcode,
            'description' => $dto->description,
            'status' => $dto->status,
            'track_stock' => $dto->track_stock,
            'type' => 'simple', // Set as simple product based on MVP
        ]);
    }

    public function listProducts(string $businessId, array $filters = [])
    {
        $query = Product::where('business_id', $businessId)->with('category');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }
}

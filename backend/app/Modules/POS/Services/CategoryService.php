<?php
namespace App\Modules\POS\Services;

use App\Modules\POS\DTOs\CreateCategoryDTO;
use App\Modules\POS\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function createCategory(CreateCategoryDTO $dto): Category
    {
        return Category::create([
            'business_id' => $dto->business_id,
            'name' => $dto->name,
            'slug' => Str::slug($dto->name) . '-' . uniqid(),
            'parent_id' => $dto->parent_id,
            'color' => $dto->color,
            'icon' => $dto->icon,
            'is_active' => $dto->is_active,
            'sort_order' => $dto->sort_order,
        ]);
    }

    public function listCategories(string $businessId)
    {
        return Category::where('business_id', $businessId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

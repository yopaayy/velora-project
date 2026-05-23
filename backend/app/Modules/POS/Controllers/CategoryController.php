<?php
namespace App\Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\POS\Requests\StoreCategoryRequest;
use App\Modules\POS\DTOs\CreateCategoryDTO;
use App\Modules\POS\Services\CategoryService;
use App\Shared\Resources\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(Request $request)
    {
        $businessId = $request->header('X-Business-Id');
        $categories = $this->categoryService->listCategories($businessId);
        
        return ApiResponse::success($categories, 'Categories retrieved successfully');
    }

    public function store(StoreCategoryRequest $request)
    {
        $businessId = $request->header('X-Business-Id');
        $dto = CreateCategoryDTO::fromRequest($request->validated(), $businessId);
        
        $category = $this->categoryService->createCategory($dto);

        return ApiResponse::success($category, 'Category created successfully', 201);
    }
}

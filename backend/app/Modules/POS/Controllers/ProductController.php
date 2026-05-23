<?php
namespace App\Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\POS\Requests\StoreProductRequest;
use App\Modules\POS\DTOs\CreateProductDTO;
use App\Modules\POS\Services\ProductService;
use App\Shared\Resources\ApiResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request)
    {
        $businessId = $request->header('X-Business-Id');
        $filters = $request->only(['search', 'category_id', 'per_page']);
        
        $products = $this->productService->listProducts($businessId, $filters);
        
        return ApiResponse::success($products, 'Products retrieved successfully');
    }

    public function store(StoreProductRequest $request)
    {
        $businessId = $request->header('X-Business-Id');
        $dto = CreateProductDTO::fromRequest($request->validated(), $businessId);
        
        $product = $this->productService->createProduct($dto);

        return ApiResponse::success($product, 'Product created successfully', 201);
    }
}

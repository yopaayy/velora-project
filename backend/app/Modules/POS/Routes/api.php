<?php

use Illuminate\Support\Facades\Route;
use App\Modules\POS\Controllers\CategoryController;
use App\Modules\POS\Controllers\ProductController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('pos')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
});

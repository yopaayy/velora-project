<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // ─── AUTHENTICATION ───
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        });
    });

    // ─── TENANT PROTECTED ROUTES (Placeholder for future modules) ───
    Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
        
        // Contoh endpoint yang sudah dilindungi Tenant Scope dan Subscription Check
        Route::get('/tenant/dashboard', function (Request $request) {
            return \App\Shared\Resources\ApiResponse::success(
                ['business_id' => $request->user()->current_business_id],
                'Selamat datang di dashboard bisnis Anda.'
            );
        });

        // Protected by branch access as well
        Route::middleware(['branch'])->group(function () {
            Route::get('/tenant/branch-data', function (Request $request) {
                return \App\Shared\Resources\ApiResponse::success(
                    ['branch_id' => $request->header('X-Branch-Id')],
                    'Data spesifik cabang.'
                );
            });
        });

    });
});


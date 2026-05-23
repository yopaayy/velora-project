<?php

namespace App\Shared\Middleware;

use App\Shared\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureTenantScope
{
    public function handle(Request $request, Closure $next)
    {
        $businessId = $request->header('X-Business-Id');

        if (!$businessId) {
            return ApiResponse::error('Missing X-Business-Id header.', 400);
        }

        $user = $request->user();

        // Verifikasi apakah user punya akses ke business ini
        $hasAccess = $user->businesses()->where('businesses.id', $businessId)->exists();

        if (!$hasAccess) {
            Log::warning("User {$user->id} attempted to access business {$businessId} without permission.");
            return ApiResponse::error('Anda tidak memiliki akses ke bisnis ini.', 403, [], 'TENANT_ACCESS_DENIED');
        }

        // Set ke auth scope sehingga kita bisa panggil di trait
        $user->current_business_id = $businessId;

        return $next($request);
    }
}

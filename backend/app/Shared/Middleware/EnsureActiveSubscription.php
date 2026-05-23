<?php

namespace App\Shared\Middleware;

use App\Modules\Tenant\Models\Business;
use App\Shared\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next)
    {
        // Require EnsureTenantScope to run first to set current_business_id
        $businessId = $request->user()?->current_business_id ?? $request->header('X-Business-Id');

        if (!$businessId) {
            return ApiResponse::error('Missing X-Business-Id header.', 400);
        }

        $business = Business::with('activeSubscription')->find($businessId);

        if (!$business || !$business->activeSubscription) {
            return ApiResponse::error('Akses ditolak: Tidak ada langganan aktif.', 403, [], 'SUBSCRIPTION_REQUIRED');
        }

        $sub = $business->activeSubscription;

        // Check trial or active or grace
        if (!$sub->isActive() && !$sub->isOnTrial() && !$sub->isInGracePeriod()) {
            return ApiResponse::error('Akses ditolak: Masa langganan telah berakhir.', 403, [], 'SUBSCRIPTION_EXPIRED');
        }

        return $next($request);
    }
}

<?php

namespace App\Shared\Middleware;

use App\Shared\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureBranchAccess
{
    public function handle(Request $request, Closure $next)
    {
        $branchId = $request->header('X-Branch-Id');

        if (!$branchId) {
            return ApiResponse::error('Missing X-Branch-Id header.', 400);
        }

        $user = $request->user();

        // Pastikan branch_id valid untuk user ini
        $hasAccess = $user->branches()->where('branches.id', $branchId)->exists();

        if (!$hasAccess) {
            Log::warning("User {$user->id} attempted to access branch {$branchId} without permission.");
            return ApiResponse::error('Anda tidak memiliki akses ke cabang ini.', 403, [], 'BRANCH_ACCESS_DENIED');
        }

        return $next($request);
    }
}

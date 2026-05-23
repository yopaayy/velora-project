<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use App\Modules\Tenant\Models\Branch;
use App\Modules\Tenant\Models\Business;
use App\Modules\Subscription\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthService
{
    public function register(RegisterDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            // 1. Create User
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'phone' => $dto->phone,
                'status' => 'active',
            ]);

            // 2. Create Business
            $business = Business::create([
                'owner_id' => $user->id,
                'name' => $dto->business_name,
                'slug' => Str::slug($dto->business_name) . '-' . strtolower(Str::random(4)),
                'phone' => $dto->business_phone,
                'business_type' => 'retail', // default type
                'currency' => 'IDR', // default currency
                'timezone' => 'Asia/Jakarta', // default timezone
                'status' => 'active',
            ]);

            // 3. Create Default Branch
            $branch = Branch::create([
                'business_id' => $business->id,
                'name' => 'Pusat',
                'code' => 'HQ',
                'type' => 'main',
                'is_main' => true,
                'is_active' => true,
            ]);

            // 4. Assign Owner Role
            $ownerRole = Role::firstOrCreate(
                ['business_id' => $business->id, 'name' => 'owner'],
                ['slug' => 'owner', 'display_name' => 'Owner', 'is_system' => true, 'level' => 1]
            );

            // Attach user to business
            $business->users()->attach($user->id, [
                'id' => Str::orderedUuid()->toString(),
                'role_id' => $ownerRole->id,
                'is_owner' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Attach user to branch
            $branch->users()->attach($user->id, [
                'id' => Str::orderedUuid()->toString(),
                'is_default' => true,
            ]);

            // 5. Assign Free Subscription Plan
            $freePlan = SubscriptionPlan::where('slug', 'free')->first();
            if ($freePlan) {
                $business->subscriptions()->create([
                    'plan_id' => $freePlan->id,
                    'billing_cycle' => 'monthly',
                    'status' => 'active',
                    'starts_at' => now(),
                    // Free plan essentially never expires, or we set a far future date
                    'ends_at' => now()->addYears(10), 
                    'auto_renew' => true,
                    'billing_mode' => 'auto',
                ]);
            }

            // 6. Generate Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->load('businesses', 'branches'),
                'token' => $token,
                'business' => $business,
            ];
        });
    }

    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Silakan hubungi admin.'],
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $token = $user->createToken($dto->device_name)->plainTextToken;

        return [
            'user' => $user->load('businesses', 'branches'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        // Revoke the current access token
        $user->currentAccessToken()->delete();
    }

    public function me(User $user): User
    {
        return $user->load(['businesses.roles.permissions', 'branches', 'ownedBusinesses.activeSubscription.plan']);
    }
}

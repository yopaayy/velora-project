<?php

namespace App\Modules\Auth\DTOs;

class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $business_name,
        public readonly ?string $phone = null,
        public readonly ?string $business_phone = null
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
            business_name: $request->input('business_name'),
            phone: $request->input('phone'),
            business_phone: $request->input('business_phone')
        );
    }
}

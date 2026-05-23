<?php

namespace App\Modules\Auth\DTOs;

class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $device_name = null
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            email: $request->input('email'),
            password: $request->input('password'),
            device_name: $request->input('device_name', 'default_device')
        );
    }
}

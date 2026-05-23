<?php
namespace App\Modules\POS\DTOs;

class CreateCategoryDTO
{
    public function __construct(
        public string $business_id,
        public string $name,
        public ?string $parent_id = null,
        public ?string $color = null,
        public ?string $icon = null,
        public bool $is_active = true,
        public int $sort_order = 0
    ) {}

    public static function fromRequest(array $data, string $businessId): self
    {
        return new self(
            business_id: $businessId,
            name: $data['name'],
            parent_id: $data['parent_id'] ?? null,
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            is_active: $data['is_active'] ?? true,
            sort_order: $data['sort_order'] ?? 0
        );
    }
}

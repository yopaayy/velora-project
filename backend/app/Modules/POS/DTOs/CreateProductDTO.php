<?php
namespace App\Modules\POS\DTOs;

class CreateProductDTO
{
    public function __construct(
        public string $business_id,
        public string $name,
        public int $cost_price,
        public int $selling_price,
        public int $stock_quantity,
        public ?string $category_id = null,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?string $description = null,
        public string $status = 'active',
        public bool $track_stock = true
    ) {}

    public static function fromRequest(array $data, string $businessId): self
    {
        return new self(
            business_id: $businessId,
            name: $data['name'],
            cost_price: $data['cost_price'] ?? 0,
            selling_price: $data['selling_price'] ?? 0,
            stock_quantity: $data['stock_quantity'] ?? 0,
            category_id: $data['category_id'] ?? null,
            sku: $data['sku'] ?? null,
            barcode: $data['barcode'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? 'active',
            track_stock: $data['track_stock'] ?? true
        );
    }
}

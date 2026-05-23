<?php

namespace Database\Seeders;

use App\Modules\Setting\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'IDR',
                'name' => 'Indonesian Rupiah',
                'symbol' => 'Rp',
                'decimal_places' => 0,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
                'symbol_position' => 'before',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'before',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SGD',
                'name' => 'Singapore Dollar',
                'symbol' => 'S$',
                'decimal_places' => 2,
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'before',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'MYR',
                'name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'decimal_places' => 2,
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'symbol_position' => 'before',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['code' => $currency['code']], $currency);
        }
    }
}

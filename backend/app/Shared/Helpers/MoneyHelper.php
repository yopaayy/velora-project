<?php

namespace App\Shared\Helpers;

class MoneyHelper
{
    public static function format(int $amount, string $currency = 'IDR', int $decimals = 0): string
    {
        $symbol = match ($currency) {
            'IDR' => 'Rp',
            'USD' => '$',
            'EUR' => '€',
            'SGD' => 'S$',
            'MYR' => 'RM',
            default => $currency,
        };

        $formatted = number_format($amount, $decimals, ',', '.');

        return "{$symbol} {$formatted}";
    }

    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }
}

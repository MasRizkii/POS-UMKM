<?php

namespace App\Services\POS;

class Money
{
    public function toMinor(int|float|string|null $amount): int
    {
        $normalized = is_float($amount) ? number_format($amount, 2, '.', '') : (string) ($amount ?? 0);

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('Invalid monetary amount.');
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $minor = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$minor : $minor;
    }

    public function fromMinor(int $minor): string
    {
        $negative = $minor < 0;
        $absolute = abs($minor);
        $amount = intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return $negative ? '-'.$amount : $amount;
    }

    public function percentage(int $subtotalMinor, int|float|string|null $percentage): int
    {
        $percentageHundredths = $this->toMinor($percentage);

        return intdiv(($subtotalMinor * $percentageHundredths) + 5000, 10000);
    }
}

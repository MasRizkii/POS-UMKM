<?php

namespace App\Enums;

enum ProductStatus: string
{
    case TERSEDIA = 'tersedia';
    case TIDAK_TERSEDIA = 'tidak_tersedia';

    public function label(): string
    {
        return match ($this) {
            self::TERSEDIA => 'Tersedia',
            self::TIDAK_TERSEDIA => 'Tidak Tersedia / Habis',
        };
    }
}

<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CASHIER = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin / Owner',
            self::CASHIER => 'Kasir',
        };
    }
}

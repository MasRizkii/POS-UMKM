<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case COMPLETED = 'completed';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Selesai',
            self::VOID => 'Dibatalkan (Void)',
        };
    }
}

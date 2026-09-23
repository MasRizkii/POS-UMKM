<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_address',
        'store_phone',
        'store_logo',
        'invoice_prefix',
        'currency',
        'timezone',
        'tax_enabled',
        'tax_percentage',
        'service_charge_enabled',
        'service_charge_percentage',
        'cash_enabled',
        'qris_enabled',
    ];

    protected function casts(): array
    {
        return [
            'tax_enabled' => 'boolean',
            'tax_percentage' => 'decimal:2',
            'service_charge_enabled' => 'boolean',
            'service_charge_percentage' => 'decimal:2',
            'cash_enabled' => 'boolean',
            'qris_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Foodislice POS UMKM',
                'store_address' => 'Jl. Kuliner No. 12, Jakarta',
                'store_phone' => '08123456789',
                'invoice_prefix' => 'INV',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'tax_enabled' => false,
                'tax_percentage' => 0.0,
                'service_charge_enabled' => false,
                'service_charge_percentage' => 0.0,
                'cash_enabled' => true,
                'qris_enabled' => true,
            ]
        );
    }
}

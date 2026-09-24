<?php

namespace App\Services\POS;

use Illuminate\Support\Str;

class InvoiceGeneratorService
{
    public function __construct(protected StoreTime $storeTime) {}

    /**
     * Menghasilkan nomor invoice unik sesuai format prefix toko, tanggal, dan nomor urut.
     * Sesuai PRD SET-2 & POS-12: Prefix dapat dikonfigurasi dan invoice unik.
     */
    public function generate(string $prefix = 'INV'): string
    {
        $date = $this->storeTime->now()->format('Ymd');

        return sprintf('%s-%s-%s', $prefix, $date, Str::ulid());
    }
}

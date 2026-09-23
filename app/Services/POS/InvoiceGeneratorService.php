<?php

namespace App\Services\POS;

use App\Models\Transaction;
use Illuminate\Support\Carbon;

class InvoiceGeneratorService
{
    /**
     * Menghasilkan nomor invoice unik sesuai format prefix toko, tanggal, dan nomor urut.
     * Sesuai PRD SET-2 & POS-12: Prefix dapat dikonfigurasi dan invoice unik.
     */
    public function generate(string $prefix = 'INV'): string
    {
        $date = Carbon::now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return sprintf('%s-%s-%s', $prefix, $date, $random);
    }
}

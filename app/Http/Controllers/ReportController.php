<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $datePreset = $request->input('date_preset', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        [$start, $end] = $this->resolveDateRange($datePreset, $startDate, $endDate);

        // Hanya transaksi COMPLETED yang dihitung (Void dikecualikan sesuai PRD REPORT-3)
        $completedTransactions = Transaction::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->get();

        $omzet = $completedTransactions->sum('total_amount');
        $trxCount = $completedTransactions->count();
        $cashSales = $completedTransactions->where('payment_method', 'cash')->sum('total_amount');
        $qrisSales = $completedTransactions->where('payment_method', 'qris')->sum('total_amount');
        $atv = $trxCount > 0 ? round($omzet / $trxCount) : 0;

        // Total Produk Terjual
        $completedIds = $completedTransactions->pluck('id');
        $itemsSold = TransactionItem::whereIn('transaction_id', $completedIds)->sum('quantity');

        // Top Produk pada periode
        $topProducts = TransactionItem::whereIn('transaction_id', $completedIds)
            ->selectRaw('product_name_snapshot, sum(quantity) as total_qty, sum(subtotal) as total_amount')
            ->groupBy('product_name_snapshot')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $cashCount = $completedTransactions->where('payment_method', 'cash')->count();
        $qrisCount = $completedTransactions->where('payment_method', 'qris')->count();

        // Tren Penjualan Jam Operasional (10:00 - 20:00)
        $hourlySales = [];
        for ($h = 10; $h <= 20; $h += 2) {
            $label = sprintf('%02d:00', $h);
            $amount = $completedTransactions->filter(function ($t) use ($h) {
                $hour = Carbon::parse($t->created_at)->hour;
                return $hour >= $h && $hour < ($h + 2);
            })->sum('total_amount');

            $hourlySales[] = [
                'hour' => $label,
                'amount' => (int) $amount,
            ];
        }

        // Riwayat Transaksi Ringkas pada Periode
        $transactions = Transaction::whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Reports/Index', [
            'metrics' => [
                'omzet' => $omzet,
                'count' => $trxCount,
                'cash' => $cashSales,
                'cash_count' => $cashCount,
                'qris' => $qrisSales,
                'qris_count' => $qrisCount,
                'items_sold' => (int) $itemsSold,
                'atv' => $atv,
            ],
            'hourlySales' => $hourlySales,
            'topProducts' => $topProducts,
            'transactions' => $transactions,
            'filters' => [
                'date_preset' => $datePreset,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Ekspor CSV Streaming sesuai PRD REPORT-4.
     * Menggunakan native StreamedResponse agar hemat memori dan langsung kompatibel dengan Excel.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $datePreset = $request->input('date_preset', 'month');
        [$start, $end] = $this->resolveDateRange($datePreset, $request->input('start_date'), $request->input('end_date'));

        $filename = 'laporan-penjualan-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM untuk Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Header CSV
            fputcsv($handle, [
                'No. Invoice',
                'Tanggal',
                'Jam',
                'Kasir',
                'Rincian Menu',
                'Subtotal',
                'Pajak',
                'Total Pembayaran',
                'Metode Bayar',
                'Status',
            ]);

            Transaction::with(['items', 'user'])
                ->whereBetween('created_at', [$start, $end])
                ->chunk(100, function ($transactions) use ($handle) {
                    foreach ($transactions as $t) {
                        $itemsSummary = $t->items->map(fn ($i) => $i->product_name_snapshot . ' (' . $i->quantity . ')')->join(', ');

                        fputcsv($handle, [
                            $t->invoice_number,
                            $t->created_at->format('Y-m-d'),
                            $t->created_at->format('H:i:s'),
                            $t->user?->name ?? 'Kasir',
                            $itemsSummary,
                            $t->subtotal,
                            $t->tax_amount,
                            $t->total_amount,
                            strtoupper($t->payment_method),
                            strtoupper($t->status),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveDateRange(string $preset, ?string $start, ?string $end): array
    {
        $now = Carbon::now();

        if ($preset === 'today') {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        } elseif ($preset === 'yesterday') {
            return [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()];
        } elseif ($preset === '7days') {
            return [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()];
        } elseif ($preset === 'month') {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        } elseif ($start && $end) {
            return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
        }

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
    }
}

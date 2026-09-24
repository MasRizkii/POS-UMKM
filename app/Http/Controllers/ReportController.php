<?php

namespace App\Http\Controllers;

use App\Services\POS\SalesQuery;
use App\Services\POS\StoreTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected SalesQuery $sales,
        protected StoreTime $storeTime,
    ) {}

    public function index(Request $request): Response
    {
        $datePreset = $request->input('date_preset', 'month');
        $range = $this->storeTime->rangeFromRequest($request, 'month');
        $query = $this->sales->within($this->sales->visibleTo($request->user()), $range);
        $metrics = $this->sales->metrics($query);
        $metrics['items_sold'] = $this->sales->itemsSold($query);
        $topProducts = $this->sales->topProducts($query);
        $hourlySales = $this->sales->hourly($query, $this->storeTime->timezone());
        $transactions = (clone $query)->latest('transactions.id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Reports/Index', [
            'metrics' => $metrics,
            'hourlySales' => $hourlySales,
            'topProducts' => $topProducts,
            'transactions' => $transactions,
            'filters' => [
                'date_preset' => $datePreset,
                'start_date' => $this->storeTime->localDate($range[0]),
                'end_date' => $this->storeTime->localDate($range[1]->subSecond()),
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
        $range = $this->storeTime->rangeFromRequest($request, 'month');
        [$start, $end] = $range;

        $filename = 'laporan-penjualan-'.$this->storeTime->localDate($start).'-'.$this->storeTime->localDate($end->subSecond()).'.csv';

        return response()->streamDownload(function () use ($request, $start, $end) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM untuk Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

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

            $this->sales->within($this->sales->visibleTo($request->user()), [$start, $end])
                ->with(['items', 'user'])
                ->chunkById(100, function ($transactions) use ($handle) {
                    foreach ($transactions as $t) {
                        $itemsSummary = $t->items->map(fn ($i) => $i->product_name_snapshot.' ('.$i->quantity.')')->join(', ');

                        fputcsv($handle, [
                            $t->invoice_number,
                            $t->created_at->setTimezone($this->storeTime->timezone())->format('Y-m-d'),
                            $t->created_at->setTimezone($this->storeTime->timezone())->format('H:i:s'),
                            $t->user?->name ?? 'Kasir',
                            $itemsSummary,
                            $t->subtotal,
                            $t->tax_amount,
                            $t->total_amount,
                            strtoupper($t->payment_method),
                            strtoupper($t->status),
                        ]);
                    }
                }, 'transactions.id', 'id');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

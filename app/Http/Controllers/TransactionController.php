<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\VoidLog;
use App\Services\Audit\AuditLoggerService;
use App\Services\POS\Money;
use App\Services\POS\SalesQuery;
use App\Services\POS\StoreTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected SalesQuery $sales,
        protected StoreTime $storeTime,
        protected Money $money,
    ) {}

    public function index(Request $request): Response
    {
        $baseQuery = $this->sales->visibleTo($request->user());
        $query = (clone $baseQuery)->with(['items', 'user', 'voidLog.user'])
            ->latest('id');

        // 1. Filter Invoice Search
        if ($search = $request->input('search')) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        // 2. Filter Metode Pembayaran
        if ($payment = $request->input('payment_method')) {
            $query->where('payment_method', $payment);
        }

        // 3. Filter Status (Completed / Void)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $datePreset = $request->input('date_preset', 'all');
        $this->sales->within($query, $this->storeTime->rangeFromRequest($request));

        // Pagination minimal 20 transaksi per halaman (PRD TRX-3)
        $transactions = $query->paginate(20)->withQueryString();

        $todayQuery = $this->sales->within((clone $baseQuery), $this->storeTime->range('today'));
        $todayMetrics = $this->sales->metrics($todayQuery);
        $todayVoidCount = (clone $todayQuery)
            ->where('status', 'void')
            ->count();
        $todayTotalCount = $todayMetrics['count'] + $todayVoidCount;
        $todayVoidPercentage = $todayTotalCount > 0 ? round(($todayVoidCount / $todayTotalCount) * 100, 1) : 0;

        $summary = [
            'today_sales_count' => $todayMetrics['count'],
            'today_sales_amount' => $todayMetrics['omzet'],
            'today_void_count' => $todayVoidCount,
            'today_void_percentage' => $todayVoidPercentage,
        ];

        $countRow = (clone $baseQuery)->selectRaw("COUNT(*) AS aggregate_count,
            COUNT(CASE WHEN transactions.payment_method = 'cash' THEN 1 END) AS cash_count,
            COUNT(CASE WHEN transactions.payment_method = 'qris' THEN 1 END) AS qris_count,
            COUNT(CASE WHEN transactions.status = 'completed' THEN 1 END) AS completed_count,
            COUNT(CASE WHEN transactions.status = 'void' THEN 1 END) AS void_count")->first();
        $counts = [
            'all' => (int) $countRow->aggregate_count,
            'cash' => (int) $countRow->cash_count,
            'qris' => (int) $countRow->qris_count,
            'completed' => (int) $countRow->completed_count,
            'void' => (int) $countRow->void_count,
        ];

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'counts' => $counts,
            'filters' => [
                'search' => $request->input('search', ''),
                'payment_method' => $request->input('payment_method', ''),
                'status' => $request->input('status', ''),
                'date_preset' => $datePreset,
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
        ]);
    }

    /**
     * Membatalkan transaksi (Void) sesuai PRD TRX-4, TRX-5, TRX-6, TRX-7.
     * Tidak menghapus transaksi dari database dan transaksi Void tidak dihitung sebagai omzet.
     */
    public function void(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $transaction = Transaction::findOrFail($id);
        if ($transaction->isVoid()) {
            return back()->with('error', 'Transaksi ini sudah dibatalkan (Void) sebelumnya.');
        }

        Gate::authorize('void', $transaction);

        DB::transaction(function () use ($transaction, $request, $validated): void {
            $lockedTransaction = Transaction::lockForUpdate()->findOrFail($transaction->id);

            if ($lockedTransaction->isVoid()) {
                abort(422, 'Transaksi ini sudah dibatalkan (Void) sebelumnya.');
            }

            $oldStatus = $lockedTransaction->status;
            $lockedTransaction->update(['status' => 'void']);

            if ($lockedTransaction->payment_method === 'cash') {
                $shift = $lockedTransaction->shift()->lockForUpdate()->firstOrFail();
                $totalMinor = $this->money->toMinor($lockedTransaction->total_amount);
                $shift->update([
                    'cash_sales' => $this->money->fromMinor($this->money->toMinor($shift->cash_sales) - $totalMinor),
                    'expected_cash' => $this->money->fromMinor($this->money->toMinor($shift->expected_cash) - $totalMinor),
                ]);
            }

            VoidLog::create([
                'transaction_id' => $lockedTransaction->id,
                'user_id' => $request->user()->id,
                'reason' => $validated['reason'],
                'void_at' => now(),
            ]);

            $this->auditLogger->log('VOID_TRANSACTION', 'Transaction', $lockedTransaction->id, ['status' => $oldStatus], ['status' => 'void', 'reason' => $validated['reason']]);
        });

        return back()->with('success', "Transaksi #{$transaction->invoice_number} berhasil di-Void.");
    }
}

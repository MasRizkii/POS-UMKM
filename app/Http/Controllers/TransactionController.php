<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\VoidLog;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function index(Request $request): Response
    {
        $query = Transaction::with(['items', 'user', 'voidLog.user'])
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

        // 4. Filter Rentang Tanggal
        $datePreset = $request->input('date_preset', 'all');
        $now = Carbon::now();

        if ($datePreset === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($datePreset === 'yesterday') {
            $query->whereDate('created_at', Carbon::yesterday());
        } elseif ($datePreset === '7days') {
            $query->where('created_at', '>=', Carbon::today()->subDays(7));
        } elseif ($datePreset === 'month') {
            $query->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        }

        // Pagination minimal 20 transaksi per halaman (PRD TRX-3)
        $transactions = $query->paginate(20)->withQueryString();

        // Agregasi KPI Hari Ini (sesuai Stitch Header)
        $todayCompleted = Transaction::whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->get();
        $todayVoidCount = Transaction::whereDate('created_at', Carbon::today())
            ->where('status', 'void')
            ->count();
        $todayTotalCount = $todayCompleted->count() + $todayVoidCount;
        $todayVoidPercentage = $todayTotalCount > 0 ? round(($todayVoidCount / $todayTotalCount) * 100, 1) : 0;

        $summary = [
            'today_sales_count' => $todayCompleted->count(),
            'today_sales_amount' => (int) $todayCompleted->sum('total_amount'),
            'today_void_count' => $todayVoidCount,
            'today_void_percentage' => $todayVoidPercentage,
        ];

        // Counter untuk filter chips
        $counts = [
            'all' => Transaction::count(),
            'cash' => Transaction::where('payment_method', 'cash')->count(),
            'qris' => Transaction::where('payment_method', 'qris')->count(),
            'completed' => Transaction::where('status', 'completed')->count(),
            'void' => Transaction::where('status', 'void')->count(),
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

        $oldStatus = $transaction->status;
        $transaction->status = 'void';
        $transaction->save();

        // Simpan alasan void, user pelaku, dan waktu void
        VoidLog::create([
            'transaction_id' => $transaction->id,
            'user_id' => $request->user()->id,
            'reason' => $validated['reason'],
            'void_at' => now(),
        ]);

        // Audit Log
        $this->auditLogger->log(
            action: 'VOID_TRANSACTION',
            entity: 'Transaction',
            entityId: $transaction->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'void', 'reason' => $validated['reason']]
        );

        return back()->with('success', "Transaksi #{$transaction->invoice_number} berhasil di-Void.");
    }
}

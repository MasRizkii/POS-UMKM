<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\POS\SalesQuery;
use App\Services\POS\StoreTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected SalesQuery $sales,
        protected StoreTime $storeTime,
    ) {}

    public function index(Request $request): Response
    {
        $todayQuery = $this->sales->within($this->sales->visibleTo($request->user()), $this->storeTime->range('today'));
        $metrics = $this->sales->metrics($todayQuery);
        $activeShift = Shift::with('user:id,name')
            ->where('status', 'open')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->first();
        $topProducts = $this->sales->topProducts($todayQuery)->map(fn ($product) => [
            'name' => $product->product_name_snapshot,
            'price' => $product->unit_price,
            'sold_count' => (int) $product->total_qty,
            'total_sales' => $product->total_amount,
        ]);
        $recentTransactions = $this->sales->visibleTo($request->user())
            ->with(['items', 'user'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'total_amount' => $transaction->total_amount,
                    'payment_method' => $transaction->payment_method,
                    'status' => $transaction->status,
                    'notes' => $transaction->notes,
                    'formatted_time' => $transaction->created_at->setTimezone($this->storeTime->timezone())->format('H:i'),
                    'items' => $transaction->items->map(fn ($item) => [
                        'product_name_snapshot' => $item->product_name_snapshot,
                        'quantity' => $item->quantity,
                    ]),
                ];
            });
        $hourlyData = $this->sales->hourly($todayQuery, $this->storeTime->timezone())
            ->map(fn (array $row) => ['time' => $row['hour'], 'total' => $row['amount']]);

        return Inertia::render('Dashboard/Index', [
            'metrics' => [
                'omzet' => $metrics['omzet'],
                'count' => $metrics['count'],
                'cash' => $metrics['cash'],
                'qris' => $metrics['qris'],
            ],
            'activeShift' => $activeShift,
            'topProducts' => $topProducts,
            'recentTransactions' => $recentTransactions,
            'hourlyData' => $hourlyData,
        ]);
    }
}

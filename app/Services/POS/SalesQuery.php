<?php

namespace App\Services\POS;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesQuery
{
    public function visibleTo(User $user): Builder
    {
        return Transaction::query()->when(! $user->isAdmin(), fn (Builder $query) => $query->where('transactions.user_id', $user->id));
    }

    /** @param array{0: mixed, 1: mixed}|null $range */
    public function within(Builder $query, ?array $range): Builder
    {
        if ($range !== null) {
            $query->where('transactions.created_at', '>=', $range[0])
                ->where('transactions.created_at', '<', $range[1]);
        }

        return $query;
    }

    /** @return array{count: int, omzet: mixed, cash: mixed, qris: mixed, cash_count: int, qris_count: int, atv: mixed} */
    public function metrics(Builder $query): array
    {
        $row = (clone $query)
            ->where('transactions.status', 'completed')
            ->selectRaw("COUNT(*) AS aggregate_count,
                COALESCE(SUM(transactions.total_amount), 0) AS omzet,
                COALESCE(SUM(CASE WHEN transactions.payment_method = 'cash' THEN transactions.total_amount ELSE 0 END), 0) AS cash,
                COALESCE(SUM(CASE WHEN transactions.payment_method = 'qris' THEN transactions.total_amount ELSE 0 END), 0) AS qris,
                COUNT(CASE WHEN transactions.payment_method = 'cash' THEN 1 END) AS cash_count,
                COUNT(CASE WHEN transactions.payment_method = 'qris' THEN 1 END) AS qris_count,
                COALESCE(AVG(transactions.total_amount), 0) AS atv")
            ->first();

        return [
            'count' => (int) $row->aggregate_count,
            'omzet' => $row->omzet,
            'cash' => $row->cash,
            'qris' => $row->qris,
            'cash_count' => (int) $row->cash_count,
            'qris_count' => (int) $row->qris_count,
            'atv' => $row->atv,
        ];
    }

    public function itemsSold(Builder $query): int
    {
        return (int) (clone $query)
            ->join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->sum('transaction_items.quantity');
    }

    public function topProducts(Builder $query, int $limit = 5): Collection
    {
        return (clone $query)
            ->join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->selectRaw('transaction_items.product_name_snapshot, MAX(transaction_items.unit_price_snapshot) AS unit_price, SUM(transaction_items.quantity) AS total_qty, SUM(transaction_items.subtotal) AS total_amount')
            ->groupBy('transaction_items.product_name_snapshot')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    public function hourly(Builder $query, string $timezone): Collection
    {
        $offset = match ($timezone) {
            'Asia/Makassar' => 8,
            'Asia/Jayapura' => 9,
            default => 7,
        };
        $hourExpression = DB::connection()->getDriverName() === 'mysql'
            ? "MOD(HOUR(DATE_ADD(transactions.created_at, INTERVAL {$offset} HOUR)), 24)"
            : "((CAST(SUBSTR(transactions.created_at, 12, 2) AS INTEGER) + {$offset}) % 24)";

        return (clone $query)
            ->where('transactions.status', 'completed')
            ->selectRaw("{$hourExpression} AS local_hour, SUM(transactions.total_amount) AS amount")
            ->groupByRaw($hourExpression)
            ->orderBy('local_hour')
            ->get()
            ->map(fn (Transaction $transaction) => [
                'hour' => sprintf('%02d:00', (int) $transaction->local_hour),
                'amount' => $transaction->amount,
            ]);
    }
}

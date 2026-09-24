<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\Audit\AuditLoggerService;
use App\Services\POS\InvoiceGeneratorService;
use App\Services\POS\Money;
use App\Services\POS\SalesQuery;
use App\Services\POS\StoreTime;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class POSController extends Controller
{
    public function __construct(
        protected InvoiceGeneratorService $invoiceGenerator,
        protected AuditLoggerService $auditLogger,
        protected Money $money,
        protected SalesQuery $sales,
        protected StoreTime $storeTime,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Ambil Kategori Aktif
        $categories = Category::where('is_active', true)->get(['id', 'name', 'icon']);

        // 2. Ambil Produk (Termasuk status ketersediaan untuk badge)
        $products = Product::with('category:id,name')
            ->orderBy('id', 'asc')
            ->get(['id', 'category_id', 'name', 'price', 'image_url', 'description', 'status']);

        // 3. Ambil Shift Aktif Kasir
        $activeShift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // 4. Pengaturan Pajak & Toko
        $setting = Setting::current();

        // 5. Quick Metrics Hari Ini
        $todayQuery = $this->sales->within($this->sales->visibleTo($user), $this->storeTime->range('today'));
        $todayOrdersCount = $this->sales->metrics($todayQuery)['count'];
        $registerTotal = $activeShift
            ? $this->money->fromMinor($this->money->toMinor($activeShift->opening_cash) + $this->money->toMinor($activeShift->cash_sales))
            : '0.00';

        return Inertia::render('POS/Index', [
            'categories' => $categories,
            'products' => $products,
            'activeShift' => $activeShift,
            'setting' => [
                'store_name' => $setting->store_name,
                'tax_enabled' => $setting->tax_enabled,
                'tax_percentage' => (float) $setting->tax_percentage,
                'service_charge_enabled' => $setting->service_charge_enabled,
                'service_charge_percentage' => (float) $setting->service_charge_percentage,
                'cash_enabled' => $setting->cash_enabled,
                'qris_enabled' => $setting->qris_enabled,
            ],
            'todayOrdersCount' => $todayOrdersCount,
            'registerTotal' => $registerTotal,
        ]);
    }

    /**
     * Checkout atomik sesuai PRD POS-16, AC-25, 7.1, 7.6.
     * Server memvalidasi ulang seluruh harga, ketersediaan produk, dan menghitung total final.
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Validasi Input Dasar
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:cash,qris'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        // 2. Pastikan Kasir Memiliki Shift Aktif
        $activeShift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $activeShift) {
            return response()->json(['message' => 'Tidak ada shift aktif. Buka shift terlebih dahulu sebelum checkout.'], 422);
        }

        // 3. Konfigurasi Pajak Toko
        $setting = Setting::current();

        if ($validated['payment_method'] === PaymentMethod::CASH->value && ! $setting->cash_enabled) {
            return response()->json(['message' => 'Pembayaran tunai sedang tidak tersedia.'], 422);
        }

        if ($validated['payment_method'] === PaymentMethod::QRIS->value && ! $setting->qris_enabled) {
            return response()->json(['message' => 'Pembayaran QRIS sedang tidak tersedia.'], 422);
        }

        try {
            return DB::transaction(function () use ($validated, $user, $activeShift, $setting) {
                $existingTransaction = Transaction::where('user_id', $user->id)
                    ->where('shift_id', $activeShift->id)
                    ->where('idempotency_key', $validated['idempotency_key'])
                    ->lockForUpdate()
                    ->first();

                if ($existingTransaction) {
                    return response()->json(['message' => 'Transaksi sudah diproses.', 'transaction' => $existingTransaction->load('items')]);
                }

                $lockedShift = Shift::whereKey($activeShift->id)->where('status', 'open')->lockForUpdate()->first();

                if (! $lockedShift) {
                    return response()->json(['message' => 'Shift sudah ditutup. Buka shift baru sebelum checkout.'], 422);
                }
                $subtotalMinor = 0;
                $itemsToCreate = [];

                // 4. Validasi Ulang Setiap Produk dari Database (Server as Single Source of Truth)
                foreach ($validated['items'] as $itemData) {
                    $product = Product::lockForUpdate()->find($itemData['product_id']);

                    if (! $product || $product->status === 'tidak_tersedia') {
                        return response()->json([
                            'message' => 'Produk '.($product?->name ?? 'item').' sedang tidak tersedia atau habis.',
                        ], 422);
                    }

                    $itemSubtotalMinor = $this->money->toMinor($product->price) * $itemData['quantity'];
                    $subtotalMinor += $itemSubtotalMinor;

                    $itemsToCreate[] = [
                        'product_id' => $product->id,
                        'product_name_snapshot' => $product->name,
                        'unit_price_snapshot' => $product->price,
                        'quantity' => $itemData['quantity'],
                        'subtotal' => $this->money->fromMinor($itemSubtotalMinor),
                        'note' => $itemData['note'] ?? null,
                    ];
                }

                $taxMinor = $setting->tax_enabled ? $this->money->percentage($subtotalMinor, $setting->tax_percentage) : 0;
                $serviceChargeMinor = $setting->service_charge_enabled
                    ? $this->money->percentage($subtotalMinor, $setting->service_charge_percentage)
                    : 0;
                $totalMinor = $subtotalMinor + $taxMinor + $serviceChargeMinor;
                $amountPaidMinor = $this->money->toMinor($validated['amount_paid']);

                if ($validated['payment_method'] === 'cash') {
                    if ($amountPaidMinor < $totalMinor) {
                        return response()->json([
                            'message' => 'Uang diterima kurang dari total pembayaran.',
                        ], 422);
                    }
                    $changeDueMinor = $amountPaidMinor - $totalMinor;
                } else {
                    $amountPaidMinor = $totalMinor;
                    $changeDueMinor = 0;
                }

                // 7. Generate Nomor Invoice Unik
                $invoiceNumber = $this->invoiceGenerator->generate($setting->invoice_prefix ?? 'POS');

                // 8. Simpan Transaksi
                $transaction = Transaction::create([
                    'invoice_number' => $invoiceNumber,
                    'idempotency_key' => $validated['idempotency_key'],
                    'user_id' => $user->id,
                    'shift_id' => $lockedShift->id,
                    'subtotal' => $this->money->fromMinor($subtotalMinor),
                    'tax_amount' => $this->money->fromMinor($taxMinor),
                    'service_charge_amount' => $this->money->fromMinor($serviceChargeMinor),
                    'total_amount' => $this->money->fromMinor($totalMinor),
                    'payment_method' => $validated['payment_method'],
                    'amount_paid' => $this->money->fromMinor($amountPaidMinor),
                    'change_due' => $this->money->fromMinor($changeDueMinor),
                    'status' => TransactionStatus::COMPLETED,
                    'notes' => implode(', ', array_map(fn ($i) => $i['product_name_snapshot'].' x'.$i['quantity'], $itemsToCreate)),
                ]);

                // 9. Simpan Snapshot Item Transaksi
                foreach ($itemsToCreate as $itemSnapshot) {
                    $transaction->items()->create($itemSnapshot);
                }

                // 10. Update Kas Shift jika Tunai
                if ($validated['payment_method'] === 'cash') {
                    $lockedShift->cash_sales = $this->money->fromMinor($this->money->toMinor($lockedShift->cash_sales) + $totalMinor);
                    $lockedShift->expected_cash = $this->money->fromMinor($this->money->toMinor($lockedShift->expected_cash) + $totalMinor);
                    $lockedShift->save();
                }

                // Load items untuk struk responsif
                $transaction->load('items');
                $this->auditLogger->log('CHECKOUT_COMPLETED', 'Transaction', $transaction->id, null, ['invoice_number' => $transaction->invoice_number, 'total_amount' => $transaction->total_amount]);

                return response()->json([
                    'message' => 'Transaksi berhasil disimpan.',
                    'transaction' => $transaction,
                ]);
            });
        } catch (QueryException $exception) {
            $existingTransaction = Transaction::where('user_id', $user->id)
                ->where('shift_id', $activeShift->id)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existingTransaction) {
                return response()->json(['message' => 'Transaksi sudah diproses.', 'transaction' => $existingTransaction->load('items')]);
            }

            throw $exception;
        }
    }
}

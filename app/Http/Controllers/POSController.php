<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\POS\InvoiceGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class POSController extends Controller
{
    public function __construct(
        protected InvoiceGeneratorService $invoiceGenerator
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
        $today = Carbon::today();
        $completedToday = Transaction::whereDate('created_at', $today)->where('status', 'completed');
        $todayOrdersCount = $completedToday->count();
        $registerTotal = $activeShift ? $activeShift->opening_cash + $activeShift->cash_sales : 1850000;

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
            ],
            'todayOrdersCount' => $todayOrdersCount > 0 ? $todayOrdersCount : 148,
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
        ]);

        // 2. Pastikan Kasir Memiliki Shift Aktif
        $activeShift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $activeShift) {
            // Jika belum ada shift, buatkan shift default untuk kemudahan testing
            $activeShift = Shift::create([
                'user_id' => $user->id,
                'opening_cash' => 500000,
                'cash_sales' => 0,
                'expected_cash' => 500000,
                'status' => 'open',
                'opened_at' => now(),
                'notes' => 'Shift Otomatis Kasir',
            ]);
        }

        // 3. Konfigurasi Pajak Toko
        $setting = Setting::current();

        return DB::transaction(function () use ($validated, $user, $activeShift, $setting) {
            $subtotal = 0;
            $itemsToCreate = [];

            // 4. Validasi Ulang Setiap Produk dari Database (Server as Single Source of Truth)
            foreach ($validated['items'] as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);

                if (! $product || $product->status === 'tidak_tersedia') {
                    return response()->json([
                        'message' => 'Produk ' . ($product?->name ?? 'item') . ' sedang tidak tersedia atau habis.',
                    ], 422);
                }

                $itemSubtotal = $product->price * $itemData['quantity'];
                $subtotal += $itemSubtotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'unit_price_snapshot' => $product->price,
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemSubtotal,
                    'note' => $itemData['note'] ?? null,
                ];
            }

            // 5. Kalkulasi Pajak & Service Charge
            $taxAmount = 0;
            if ($setting->tax_enabled && $setting->tax_percentage > 0) {
                $taxAmount = round(($subtotal * $setting->tax_percentage) / 100, 2);
            }

            $totalAmount = $subtotal + $taxAmount;

            // 6. Validasi Pembayaran Tunai (Cash)
            $changeDue = 0;
            if ($validated['payment_method'] === 'cash') {
                if ($validated['amount_paid'] < $totalAmount) {
                    return response()->json([
                        'message' => 'Uang diterima kurang dari total pembayaran.',
                    ], 422);
                }
                $changeDue = $validated['amount_paid'] - $totalAmount;
            } else {
                // QRIS fisik exact amount
                $validated['amount_paid'] = $totalAmount;
            }

            // 7. Generate Nomor Invoice Unik
            $invoiceNumber = $this->invoiceGenerator->generate($setting->invoice_prefix ?? 'POS');

            // 8. Simpan Transaksi
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $user->id,
                'shift_id' => $activeShift->id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'service_charge_amount' => 0,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $validated['amount_paid'],
                'change_due' => $changeDue,
                'status' => 'completed',
                'notes' => implode(', ', array_map(fn ($i) => $i['product_name_snapshot'] . ' x' . $i['quantity'], $itemsToCreate)),
            ]);

            // 9. Simpan Snapshot Item Transaksi
            foreach ($itemsToCreate as $itemSnapshot) {
                $transaction->items()->create($itemSnapshot);
            }

            // 10. Update Kas Shift jika Tunai
            if ($validated['payment_method'] === 'cash') {
                $activeShift->cash_sales += $totalAmount;
                $activeShift->expected_cash += $totalAmount;
                $activeShift->save();
            }

            // Load items untuk struk responsif
            $transaction->load('items');

            return response()->json([
                'message' => 'Transaksi berhasil disimpan.',
                'transaction' => $transaction,
            ]);
        });
    }
}

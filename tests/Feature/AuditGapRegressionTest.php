<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditGapRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function sale(User $user, string $time, string $method = 'cash', string $status = 'completed', int $quantity = 1): Transaction
    {
        $shift = Shift::firstOrCreate(['user_id' => $user->id, 'status' => 'open'], ['opening_cash' => 0, 'expected_cash' => 0]);
        $category = Category::firstOrCreate(['name' => 'Minuman'], ['is_active' => true]);
        $product = Product::firstOrCreate(['name' => 'Teh'], ['category_id' => $category->id, 'price' => 10000, 'status' => 'tersedia']);
        $transaction = Transaction::create([
            'invoice_number' => 'INV-'.Str::uuid(), 'user_id' => $user->id, 'shift_id' => $shift->id,
            'subtotal' => 10000 * $quantity, 'tax_amount' => 1000 * $quantity, 'total_amount' => 11000 * $quantity,
            'payment_method' => $method, 'amount_paid' => 11000 * $quantity, 'status' => $status,
        ]);
        $transaction->forceFill(['created_at' => $time])->save();
        $transaction->items()->create(['product_id' => $product->id, 'product_name_snapshot' => 'Teh', 'unit_price_snapshot' => 10000, 'quantity' => $quantity, 'subtotal' => 10000 * $quantity]);

        return $transaction;
    }

    public function test_dashboard_and_report_match_completed_sales_in_store_day(): void
    {
        $this->travelTo(Carbon::parse('2026-09-24 00:00:00', 'UTC'));
        Setting::current()->update(['timezone' => 'Asia/Jakarta']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->sale($admin, '2026-09-23 17:00:00');
        $this->sale($admin, '2026-09-24 01:00:00', 'qris', 'completed', 2);
        $this->sale($admin, '2026-09-24 01:00:00', 'cash', 'void', 9);
        $this->sale($admin, '2026-09-23 16:59:59', 'cash', 'completed', 8);

        foreach (['/dashboard', '/reports?date_preset=today'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('metrics.omzet', fn ($value) => (string) $value === '33000' || (string) $value === '33000.00')
                ->where('metrics.count', 2)
                ->where('metrics.cash', fn ($value) => (int) $value === 11000)
                ->where('metrics.qris', fn ($value) => (int) $value === 22000));
        }
        $this->get('/reports?date_preset=today')->assertInertia(fn (Assert $page) => $page
            ->where('metrics.items_sold', 3)->where('metrics.atv', fn ($value) => (int) $value === 16500)
            ->where('topProducts.0.total_qty', fn ($value) => (int) $value === 3)
            ->where('topProducts.0.total_amount', fn ($value) => (int) $value === 30000)
            ->where('hourlySales.0.hour', '00:00')->where('hourlySales.1.hour', '08:00')
            ->where('filters.start_date', '2026-09-24'));
    }

    public function test_cashier_counts_and_summary_exclude_other_users(): void
    {
        $this->travelTo(Carbon::parse('2026-09-24 00:00:00', 'UTC'));
        Setting::current();
        $cashier = User::factory()->create(['role' => 'cashier']);
        $other = User::factory()->create(['role' => 'cashier']);
        $this->sale($cashier, '2026-09-24 00:00:00');
        $this->sale($cashier, '2026-09-24 00:00:00', 'qris', 'void');
        $this->sale($other, '2026-09-24 00:00:00', 'qris', 'completed', 8);

        $this->actingAs($cashier)->get('/transactions')->assertInertia(fn (Assert $page) => $page
            ->has('transactions.data', 2)->where('counts.all', 2)->where('counts.cash', 1)->where('counts.qris', 1)
            ->where('summary.today_sales_count', 1)->where('summary.today_sales_amount', fn ($value) => (int) $value === 11000));
        $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->where('metrics.count', 1)->has('recentTransactions', 2));
    }

    public function test_seven_days_and_custom_range_use_store_calendar_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-09-24 00:00:00', 'UTC'));
        Setting::current()->update(['timezone' => 'Asia/Jayapura']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->sale($admin, '2026-09-17 15:00:00');
        $this->sale($admin, '2026-09-17 14:59:59');

        $this->actingAs($admin)->get('/reports?date_preset=7days')->assertInertia(fn (Assert $page) => $page->where('metrics.count', 1)->where('filters.start_date', '2026-09-18'));
        $this->get('/reports?date_preset=custom&start_date=2026-09-18&end_date=2026-09-18')->assertInertia(fn (Assert $page) => $page->where('metrics.count', 1));
        $this->get('/reports?date_preset=custom&start_date=2026-09-20&end_date=2026-09-18')->assertSessionHasErrors('end_date');
    }

    public function test_empty_report_returns_zero_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/reports')->assertInertia(fn (Assert $page) => $page
            ->where('metrics.count', 0)->where('metrics.cash_count', 0)->where('metrics.qris_count', 0)
            ->where('metrics.items_sold', 0)->where('metrics.atv', fn ($value) => (int) $value === 0)->has('topProducts', 0));
    }
}

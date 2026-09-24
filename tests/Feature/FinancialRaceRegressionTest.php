<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialRaceRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function checkoutFixture(): array
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Teh', 'price' => '10000.00', 'status' => 'tersedia']);
        $shift = Shift::create(['user_id' => $cashier->id, 'opening_cash' => '0.00', 'expected_cash' => '0.00', 'status' => 'open']);

        return [$cashier, $product, $shift];
    }

    public function test_database_constraint_prevents_concurrent_active_shifts(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Shift::create(['user_id' => $cashier->id, 'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'open']);

        try {
            Shift::create(['user_id' => $cashier->id, 'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'open']);
            $this->fail('Unique active shift constraint was not enforced.');
        } catch (QueryException) {
            $this->assertDatabaseCount('shifts', 1);
            $this->assertDatabaseHas('shifts', ['user_id' => $cashier->id, 'active_user_id' => $cashier->id, 'status' => 'open']);
        }
    }

    public function test_idempotent_retry_returns_original_transaction_and_financial_state(): void
    {
        [$cashier, $product, $shift] = $this->checkoutFixture();
        $payload = [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_paid' => 20000,
            'idempotency_key' => (string) Str::uuid(),
        ];

        $firstId = $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk()->json('transaction.id');
        $retryId = $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk()->json('transaction.id');

        $this->assertSame($firstId, $retryId);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'cash_sales' => 10000, 'expected_cash' => 10000]);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_idempotency_key_is_scoped_to_cashier_and_shift(): void
    {
        [$firstCashier, $product] = $this->checkoutFixture();
        $secondCashier = User::factory()->create(['role' => 'cashier']);
        Shift::create(['user_id' => $secondCashier->id, 'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'open']);
        $key = (string) Str::uuid();
        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'payment_method' => 'qris', 'amount_paid' => 0, 'idempotency_key' => $key];

        $firstId = $this->actingAs($firstCashier)->postJson('/pos/checkout', $payload)->assertOk()->json('transaction.id');
        $secondId = $this->actingAs($secondCashier)->postJson('/pos/checkout', $payload)->assertOk()->json('transaction.id');

        $this->assertNotSame($firstId, $secondId);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_money_calculation_uses_exact_minor_units(): void
    {
        [$cashier, $product] = $this->checkoutFixture();
        $product->update(['price' => '0.10']);
        Setting::current()->update(['tax_enabled' => true, 'tax_percentage' => '10.00', 'service_charge_enabled' => true, 'service_charge_percentage' => '10.00']);

        $this->actingAs($cashier)->postJson('/pos/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'payment_method' => 'cash',
            'amount_paid' => '0.40',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'subtotal' => 0.30,
            'tax_amount' => 0.03,
            'service_charge_amount' => 0.03,
            'total_amount' => 0.36,
            'amount_paid' => 0.40,
            'change_due' => 0.04,
        ]);

        $transaction = Transaction::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post("/transactions/{$transaction->id}/void", ['reason' => 'Koreksi nominal'])->assertSessionHas('success');
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'void']);
        $this->assertDatabaseHas('shifts', ['id' => $transaction->shift_id, 'cash_sales' => 0, 'expected_cash' => 0]);
    }
}

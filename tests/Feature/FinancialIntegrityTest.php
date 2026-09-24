<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function cashierWithProduct(): array
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Teh', 'price' => 10000, 'status' => 'tersedia']);

        return [$cashier, $product];
    }

    private function payload(Product $product, string $method = 'cash', ?string $key = null): array
    {
        return ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'payment_method' => $method, 'amount_paid' => 50000, 'idempotency_key' => $key ?? (string) Str::uuid()];
    }

    public function test_checkout_requires_active_shift(): void
    {
        [$cashier, $product] = $this->cashierWithProduct();
        $this->actingAs($cashier)->postJson('/pos/checkout', $this->payload($product))->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_open_shift_prevents_second_active_shift_and_close_persists_reconciliation(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->post('/shifts/open', ['opening_cash' => 100000])->assertSessionHas('success');
        $this->actingAs($cashier)->post('/shifts/open', ['opening_cash' => 100000])->assertUnprocessable();
        $shift = Shift::firstOrFail();
        $this->actingAs($cashier)->post("/shifts/{$shift->id}/close", ['actual_cash' => 95000])->assertSessionHas('success');
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'status' => 'closed', 'expected_cash' => 100000, 'actual_cash' => 95000, 'difference' => -5000]);
    }

    public function test_tax_and_service_charge_are_saved_from_server_settings(): void
    {
        [$cashier, $product] = $this->cashierWithProduct();
        Shift::create(['user_id' => $cashier->id, 'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'open']);
        Setting::current()->update(['tax_enabled' => true, 'tax_percentage' => 10, 'service_charge_enabled' => true, 'service_charge_percentage' => 5]);
        $this->actingAs($cashier)->postJson('/pos/checkout', $this->payload($product))->assertOk();
        $this->assertDatabaseHas('transactions', ['subtotal' => 10000, 'tax_amount' => 1000, 'service_charge_amount' => 500, 'total_amount' => 11500]);
    }

    public function test_disabled_payment_methods_are_rejected_without_transaction(): void
    {
        [$cashier, $product] = $this->cashierWithProduct();
        Shift::create(['user_id' => $cashier->id, 'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'open']);
        Setting::current()->update(['cash_enabled' => false]);
        $this->actingAs($cashier)->postJson('/pos/checkout', $this->payload($product))->assertUnprocessable();
        Setting::current()->update(['cash_enabled' => true, 'qris_enabled' => false]);
        $this->actingAs($cashier)->postJson('/pos/checkout', $this->payload($product, 'qris'))->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_is_idempotent_for_duplicate_submit(): void
    {
        [$cashier, $product] = $this->cashierWithProduct();
        Shift::create(['user_id' => $cashier->id, 'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'open']);
        $payload = $this->payload($product, 'cash', 'b088d85e-940d-4c83-a025-000000000099');
        $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk();
        $this->actingAs($cashier)->postJson('/pos/checkout', $payload)->assertOk();
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 1);
    }
}

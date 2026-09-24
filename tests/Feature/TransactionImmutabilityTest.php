<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return ['cashier' => ['cashier'], 'admin' => ['admin']];
    }

    private function completedTransaction(User $user): Transaction
    {
        $shift = Shift::create(['user_id' => $user->id, 'opening_cash' => 50000, 'cash_sales' => 20000, 'expected_cash' => 70000, 'status' => 'open']);
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Minuman', 'is_active' => true])->id,
            'name' => 'Teh', 'price' => 10000, 'status' => 'tersedia',
        ]);
        $transaction = Transaction::create([
            'invoice_number' => 'INV-IMMUTABLE-1', 'idempotency_key' => 'a3110000-0000-4000-8000-000000000001',
            'user_id' => $user->id, 'shift_id' => $shift->id, 'subtotal' => 20000,
            'tax_amount' => 0, 'service_charge_amount' => 0, 'total_amount' => 20000,
            'payment_method' => 'cash', 'amount_paid' => 25000, 'change_due' => 5000, 'status' => 'completed',
        ]);
        $transaction->items()->create([
            'product_id' => $product->id, 'product_name_snapshot' => 'Teh',
            'unit_price_snapshot' => 10000, 'quantity' => 2, 'subtotal' => 20000, 'note' => 'Tanpa es',
        ]);

        return $transaction->fresh();
    }

    #[DataProvider('roles')]
    public function test_normal_transaction_and_item_mutations_are_unavailable_and_preserve_database(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $transaction = $this->completedTransaction($user);
        $item = $transaction->items()->firstOrFail();
        $beforeTransaction = $transaction->getAttributes();
        $beforeItem = $item->getAttributes();
        $beforeShift = $transaction->shift->getAttributes();
        $this->actingAs($user);

        foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
            foreach (["/transactions/{$transaction->id}", "/transactions/{$transaction->id}/items/{$item->id}", "/transaction-items/{$item->id}"] as $uri) {
                $this->json($method, $uri, ['total_amount' => 1, 'status' => 'void', 'quantity' => 99, 'subtotal' => 1])
                    ->assertNotFound();
                $this->assertSame($beforeTransaction, $transaction->fresh()->getAttributes());
                $this->assertSame($beforeItem, $item->fresh()->getAttributes());
            }
        }

        $this->assertSame($beforeShift, $transaction->shift->fresh()->getAttributes());
        $this->assertDatabaseCount('void_logs', 0);
    }

    #[DataProvider('roles')]
    public function test_policy_denies_update_delete_and_allows_only_admin_void(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $transaction = $this->completedTransaction($user);

        foreach (['update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, $transaction));
        }
        $this->assertSame($role === 'admin', Gate::forUser($user)->allows('void', $transaction));
    }

    #[DataProvider('roles')]
    public function test_checkout_replay_cannot_change_completed_transaction_or_its_items(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $transaction = $this->completedTransaction($user);
        $item = $transaction->items()->firstOrFail();
        $before = $transaction->getAttributes();
        $itemBefore = $item->getAttributes();

        $this->actingAs($user)->postJson('/pos/checkout', [
            'idempotency_key' => $transaction->idempotency_key,
            'transaction_id' => $transaction->id, 'total_amount' => 1, 'status' => 'void',
            'items' => [['id' => $item->id, 'product_id' => $item->product_id, 'quantity' => 99, 'note' => 'Changed']],
            'payment_method' => 'cash', 'amount_paid' => 1000000,
        ])->assertOk();

        $this->assertSame($before, $transaction->fresh()->getAttributes());
        $this->assertSame($itemBefore, $item->fresh()->getAttributes());
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('shifts', ['id' => $transaction->shift_id, 'cash_sales' => 20000, 'expected_cash' => 70000]);
    }

    public function test_admin_void_preserves_financial_snapshots_and_items_and_records_correction(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $transaction = $this->completedTransaction($admin);
        $item = $transaction->items()->firstOrFail();
        $before = $transaction->only(['invoice_number', 'subtotal', 'tax_amount', 'service_charge_amount', 'total_amount', 'amount_paid', 'change_due']);
        $itemBefore = $item->getAttributes();

        $this->actingAs($admin)->post("/transactions/{$transaction->id}/void", ['reason' => 'Pesanan dibatalkan'])
            ->assertSessionHas('success');

        $this->assertSame($before, $transaction->fresh()->only(array_keys($before)));
        $this->assertSame($itemBefore, $item->fresh()->getAttributes());
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'void']);
        $this->assertDatabaseHas('void_logs', ['transaction_id' => $transaction->id, 'user_id' => $admin->id, 'reason' => 'Pesanan dibatalkan']);
        $this->assertDatabaseHas('shifts', ['id' => $transaction->shift_id, 'cash_sales' => 0, 'expected_cash' => 50000]);
    }

    public function test_route_surface_exposes_only_checkout_and_void_as_transaction_mutations(): void
    {
        $mutations = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => count(array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) > 0)
            ->filter(fn ($route) => str_contains($route->uri(), 'transaction') || str_contains($route->getActionName(), 'Transaction') || str_contains($route->uri(), 'pos'))
            ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())->sort()->values()->all();

        $this->assertSame(['POST pos/checkout', 'POST transactions/{id}/void'], $mutations);
    }
}

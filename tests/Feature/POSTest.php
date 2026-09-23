<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_view_pos_terminal(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Es Teh Manis',
            'price' => 5000,
            'status' => 'tersedia',
        ]);

        $response = $this->actingAs($cashier)->get('/pos');

        $response->assertOk();
    }

    public function test_cashier_can_checkout_with_cash(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Es Teh Manis',
            'price' => 5000,
            'status' => 'tersedia',
        ]);

        $shift = Shift::create([
            'user_id' => $cashier->id,
            'opening_cash' => 100000,
            'cash_sales' => 0,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);

        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'note' => 'Less sugar',
                ],
            ],
            'payment_method' => 'cash',
            'amount_paid' => 20000,
        ];

        $response = $this->actingAs($cashier)->postJson('/pos/checkout', $payload);

        $response->assertOk();
        $response->assertJsonPath('message', 'Transaksi berhasil disimpan.');
        $response->assertJsonPath('transaction.total_amount', '10000.00');
        $response->assertJsonPath('transaction.change_due', '10000.00');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'total_amount' => 10000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $product->id,
            'product_name_snapshot' => 'Es Teh Manis',
            'quantity' => 2,
            'subtotal' => 10000,
            'note' => 'Less sugar',
        ]);

        // Shift cash sales updated
        $shift->refresh();
        $this->assertEquals(10000, $shift->cash_sales);
        $this->assertEquals(110000, $shift->expected_cash);
    }

    public function test_cannot_checkout_product_that_is_out_of_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Es Jeruk',
            'price' => 7000,
            'status' => 'tidak_tersedia',
        ]);

        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'cash',
            'amount_paid' => 10000,
        ];

        $response = $this->actingAs($cashier)->postJson('/pos/checkout', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Produk Es Jeruk sedang tidak tersedia atau habis.');
    }
}

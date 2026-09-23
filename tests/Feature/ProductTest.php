<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Makanan Utama', 'is_active' => true]);

        // 1. Create Product
        $response = $this->actingAs($admin)->post('/products', [
            'category_id' => $category->id,
            'name' => 'Nasi Goreng Spesial',
            'price' => 25000,
            'status' => 'tersedia',
        ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', ['name' => 'Nasi Goreng Spesial']);

        $product = Product::first();

        // 2. Update Product
        $updateResponse = $this->actingAs($admin)->put("/products/{$product->id}", [
            'category_id' => $category->id,
            'name' => 'Nasi Goreng Jumbo',
            'price' => 30000,
            'status' => 'tersedia',
        ]);
        $updateResponse->assertSessionHas('success');
        $this->assertEquals('Nasi Goreng Jumbo', $product->fresh()->name);

        // 3. Toggle Status
        $toggleResponse = $this->actingAs($admin)->patch("/products/{$product->id}/toggle-status");
        $toggleResponse->assertSessionHas('success');
        $this->assertEquals('tidak_tersedia', $product->fresh()->status);

        // 4. Soft Delete Product
        $deleteResponse = $this->actingAs($admin)->delete("/products/{$product->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertTrue($product->fresh()->trashed());
    }

    public function test_cashier_cannot_access_product_management(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/products');
        $response->assertForbidden();
    }

    public function test_cannot_delete_category_with_active_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Susu Gula Aren',
            'price' => 18000,
            'status' => 'tersedia',
        ]);

        $response = $this->actingAs($admin)->delete("/categories/{$category->id}");
        $response->assertSessionHas('error');
        $this->assertFalse($category->fresh()->trashed());
    }
}

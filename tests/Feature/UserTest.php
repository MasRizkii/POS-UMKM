<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // 1. Create Cashier
        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Kasir Baru',
            'email' => 'kasirbaru@foodislice.com',
            'password' => 'password123',
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $response->assertSessionHas('success');

        $user = User::where('email', 'kasirbaru@foodislice.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));

        // 2. Update User
        $updateResponse = $this->actingAs($admin)->put("/users/{$user->id}", [
            'name' => 'Kasir Berpengalaman',
            'email' => 'kasirbaru@foodislice.com',
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $updateResponse->assertSessionHas('success');
        $this->assertEquals('Kasir Berpengalaman', $user->fresh()->name);

        // 3. Soft Delete User
        $deleteResponse = $this->actingAs($admin)->delete("/users/{$user->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertTrue($user->fresh()->trashed());
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");
        $response->assertSessionHas('error');
        $this->assertFalse($admin->fresh()->trashed());
    }

    public function test_cashier_cannot_access_user_management(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/users');
        $response->assertForbidden();
    }
}

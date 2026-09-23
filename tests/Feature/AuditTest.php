<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'LOGIN_SUCCESS',
            'entity' => 'User',
            'entity_id' => $admin->id,
            'new_values' => ['ip' => '127.0.0.1'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/audits');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Audits/Index')
            ->has('audits.data', 1)
        );
    }

    public function test_cashier_cannot_access_audit_logs(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/audits');
        $response->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function createShift(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id,
            'opening_cash' => 100000,
            'cash_sales' => 0,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);
    }

    public function test_admin_can_view_reports_and_omzet_excludes_void(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shift = $this->createShift($admin);

        // 1 Completed Transaction
        Transaction::create([
            'invoice_number' => 'INV-20260922-0010',
            'user_id' => $admin->id,
            'shift_id' => $shift->id,
            'subtotal' => 100000,
            'tax_amount' => 10000,
            'service_charge' => 0,
            'total_amount' => 110000,
            'payment_method' => 'cash',
            'amount_paid' => 110000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        // 1 Voided Transaction (Harus dikecualikan dari omzet)
        Transaction::create([
            'invoice_number' => 'INV-20260922-0011',
            'user_id' => $admin->id,
            'shift_id' => $shift->id,
            'subtotal' => 50000,
            'tax_amount' => 5000,
            'service_charge' => 0,
            'total_amount' => 55000,
            'payment_method' => 'cash',
            'amount_paid' => 55000,
            'change_amount' => 0,
            'status' => 'void',
        ]);

        $response = $this->actingAs($admin)->get('/reports?date_preset=month');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('metrics.omzet', 110000)
            ->where('metrics.count', 1)
        );
    }

    public function test_admin_can_export_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/reports/export-csv?date_preset=month');
        $response->assertOk();
        $this->assertTrue($response->headers->get('content-type') === 'text/csv; charset=UTF-8');
    }

    public function test_cashier_cannot_view_reports(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/reports');
        $response->assertForbidden();
    }
}

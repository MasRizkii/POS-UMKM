<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
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

    public function test_can_list_transactions_with_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shift = $this->createShift($admin);

        Transaction::create([
            'invoice_number' => 'INV-20260922-0001',
            'user_id' => $admin->id,
            'shift_id' => $shift->id,
            'subtotal' => 50000,
            'tax_amount' => 5000,
            'service_charge' => 0,
            'total_amount' => 55000,
            'payment_method' => 'cash',
            'amount_paid' => 60000,
            'change_amount' => 5000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get('/transactions');
        $response->assertOk();
    }

    public function test_can_void_transaction_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shift = $this->createShift($admin);

        $transaction = Transaction::create([
            'invoice_number' => 'INV-20260922-0002',
            'user_id' => $admin->id,
            'shift_id' => $shift->id,
            'subtotal' => 20000,
            'tax_amount' => 2000,
            'service_charge' => 0,
            'total_amount' => 22000,
            'payment_method' => 'cash',
            'amount_paid' => 30000,
            'change_amount' => 8000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->post("/transactions/{$transaction->id}/void", [
            'reason' => 'Pelanggan salah pesan menu dan minta refund',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('void', $transaction->fresh()->status);
        $this->assertDatabaseHas('void_logs', [
            'transaction_id' => $transaction->id,
            'user_id' => $admin->id,
            'reason' => 'Pelanggan salah pesan menu dan minta refund',
        ]);
    }

    public function test_cannot_void_already_voided_transaction(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shift = $this->createShift($admin);

        $transaction = Transaction::create([
            'invoice_number' => 'INV-20260922-0003',
            'user_id' => $admin->id,
            'shift_id' => $shift->id,
            'subtotal' => 20000,
            'tax_amount' => 2000,
            'service_charge' => 0,
            'total_amount' => 22000,
            'payment_method' => 'cash',
            'amount_paid' => 30000,
            'change_amount' => 8000,
            'status' => 'void',
        ]);

        $response = $this->actingAs($admin)->post("/transactions/{$transaction->id}/void", [
            'reason' => 'Alasan ganda',
        ]);

        $response->assertSessionHas('error');
    }
}

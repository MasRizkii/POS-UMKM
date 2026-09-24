<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/settings', [
            'store_name' => 'Kedai Kopi Bahagia',
            'store_address' => 'Jl. Merdeka No. 45',
            'store_phone' => '081299998888',
            'currency' => 'IDR',
            'invoice_prefix' => 'KPB',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_enabled' => true,
            'tax_percentage' => 11.0,
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.0,
            'cash_enabled' => true,
            'qris_enabled' => true,
        ]);

        $response->assertSessionHas('success');

        $setting = Setting::current();
        $this->assertEquals('Kedai Kopi Bahagia', $setting->store_name);
        $this->assertEquals('KPB', $setting->invoice_prefix);
        $this->assertEquals(11.0, (float) $setting->tax_percentage);
    }

    public function test_cannot_disable_both_cash_and_qris(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/settings', [
            'store_name' => 'Kedai Kopi Bahagia',
            'invoice_prefix' => 'KPB',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_enabled' => true,
            'tax_percentage' => 10.0,
            'service_charge_enabled' => false,
            'service_charge_percentage' => 0,
            'cash_enabled' => false,
            'qris_enabled' => false,
        ]);

        $response->assertSessionHasErrors('cash_enabled');
    }

    public function test_cashier_cannot_access_settings(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/settings');
        $response->assertForbidden();
    }
}

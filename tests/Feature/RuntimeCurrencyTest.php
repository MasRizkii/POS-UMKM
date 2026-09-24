<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RuntimeCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_currency_is_read_from_current_settings_on_each_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::current();

        $this->actingAs($admin)->get('/pos')->assertInertia(fn (Assert $page) => $page->where('store.currency', 'IDR'));

        // A different persisted value proves the shared prop isn't a hardcoded IDR.
        // The settings UI/API intentionally still permits only IDR in the MVP.
        $setting->update(['currency' => 'USD']);
        $this->get('/pos')->assertInertia(fn (Assert $page) => $page->where('store.currency', 'USD'));
    }

    public function test_shared_timezone_is_read_from_current_settings_on_each_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::current();

        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('store.timezone', 'Asia/Jakarta'));
        $setting->update(['timezone' => 'Asia/Jayapura']);
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('store.timezone', 'Asia/Jayapura'));
    }
}

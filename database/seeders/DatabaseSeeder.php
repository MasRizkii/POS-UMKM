<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Settings Toko
        $setting = Setting::firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Foodislice POS UMKM',
                'store_address' => 'Jl. Boulevard Raya No. 8, Jakarta',
                'store_phone' => '081298765432',
                'store_logo' => null,
                'invoice_prefix' => 'POS',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'tax_enabled' => false,
                'tax_percentage' => 0.00,
                'service_charge_enabled' => false,
                'service_charge_percentage' => 0.00,
                'cash_enabled' => true,
                'qris_enabled' => true,
            ]
        );

        // 2. Users (Admin & Cashier) dengan Laravel Hash
        $admin = User::firstOrCreate(
            ['email' => 'admin@foodislice.com'],
            [
                'name' => 'Owner Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $cashier = User::firstOrCreate(
            ['email' => 'kasir@foodislice.com'],
            [
                'name' => 'Sarah Jenkins',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 3. Categories
        $catTeh = Category::firstOrCreate(['name' => 'Es Teh'], ['icon' => '🧊', 'is_active' => true]);
        $catJeruk = Category::firstOrCreate(['name' => 'Es Jeruk'], ['icon' => '🍊', 'is_active' => true]);
        $catBlend = Category::firstOrCreate(['name' => 'Pop Ice & Blend'], ['icon' => '🥤', 'is_active' => true]);
        $catKopi = Category::firstOrCreate(['name' => 'Kopi & Susu'], ['icon' => '☕', 'is_active' => true]);
        $catSnack = Category::firstOrCreate(['name' => 'Snack & Makan'], ['icon' => '🍟', 'is_active' => true]);

        // 4. Products dari Stitch Screen
        $prodTeh = Product::firstOrCreate(
            ['name' => 'Es Teh Manis Segar'],
            [
                'category_id' => $catTeh->id,
                'price' => 5000,
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAhF4qNA8ntfM8pczl8iG7tKgry0801QA6dXNCnarE9ILw_x3nmcwUNONCkDZlo7SKOLq6sE6niCsrXhx8d1xzQ8E00W6QyZnzaJd4hJ1o58RJ48ZP4_1t0nrOHVnnQamWgMOduGHzfX1Pml37TyN0B949TVloCapGPsEYkIIoCLjIyFb0dyKPXbmUIC5kmHuPgQCGof3HhkSR5gfARJ2ym3wzETNZaH2Jeii75t2k57e_CJMVScWdCVg',
                'description' => null,
                'status' => 'tersedia',
            ]
        );

        $prodJeruk = Product::firstOrCreate(
            ['name' => 'Es Jeruk Peras'],
            [
                'category_id' => $catJeruk->id,
                'price' => 7000,
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBdWncoXqh1ivwXa0m43BwcD9OkiN2IMhQeJNXRAnOVMFcexLXp4BP_JtrV9S--yJ3Yj0A8huQGyevhptIcQRj__uxagNdWR47Fg0uRFeinztbwNtyyXVRnEPDtObPh3XuYJ9riU8rIRF-MJ7zPxy5OhH_rj2_QhQYCCWsolkFQh_OVXZIkZa3aGzmk_lKsgpFsAMUDCFfoBhdp_xKmGVkg7_S2VEKe9BqOxlXKQyjRgmN4ADujwOUkOQ',
                'description' => null,
                'status' => 'tersedia',
            ]
        );

        $prodPopIce = Product::firstOrCreate(
            ['name' => 'Pop Ice Blender Rainbow'],
            [
                'category_id' => $catBlend->id,
                'price' => 10000,
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD3HYJdPVRGOUhYky1lVgpzn32ptKsv1H36i5WmiwNZuXKv3XLwL8VzL53INPmhV6RB2ieIhUw3bKNmKZ25hMKewXRSqaqNlc9-FU2k5pucEhAEuIM26B8tBOYYOm2LhhtFRXNyx9YoIC4dFwXeEEsbi1HfCFPPWnxlK-AcJdtqlGDOA_igNWjkQv2GynUkBO1eHNic5KoJxtr-tQgydfnGNkgLlvcMM3MmBBcJkIOQWEw2zRAPZtX2fw',
                'description' => null,
                'status' => 'tersedia',
            ]
        );

        $prodKopi = Product::firstOrCreate(
            ['name' => 'Es Kopi Susu Senja'],
            [
                'category_id' => $catKopi->id,
                'price' => 18000,
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCva-96PdPO3YVNWqt90M-st9yNlE5-8Gw9zFVQhspB7uNlE1UiGo0fub2etoX3v2fJbPg_UHEqm8B2r5MLLfXFKyctyDptbu69oCPmelSJBe4UTMBpmC9S_tpBAmKCYf6ceQbZMLSYCIdKSuZYLDhG8PAniCLPvBd8OUT0kwM-wzoptZVwQuXv_ivsbNjnLXJk_2waynejW-CLLT9hbXCVb-l_gx_Veu0kRPvmDURSmFzt2kCod8fC-A',
                'description' => null,
                'status' => 'tersedia',
            ]
        );

        $prodMatcha = Product::firstOrCreate(
            ['name' => 'Matcha Cream Latte'],
            [
                'category_id' => $catKopi->id,
                'price' => 22000,
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDzy3RZp_cJh8cq6xI16i9J89Dn3OTht5U-BkYKlHTyWq9AyB_FXwYp6cOAdc--nmEHQUlWCt2SxtzLqZWfVwI6XmnUZisxcOig7K20NaKduOKbIsKItJhLhjoT6xERq_nKErErpA16wNNn7EYCaLUkDO8vbSjPYfL9ZKrpsBgt974YlFMMGLQGU8CMHmhNDZ9rNAn49a7L1BliFbVdt5NEPS2DXKQ33GakK2ne_QSgBMqVBE6iH7hAsw',
                'description' => null,
                'status' => 'tersedia',
            ]
        );

        $prodFries = Product::firstOrCreate(
            ['name' => 'French Fries Gurih'],
            [
                'category_id' => $catSnack->id,
                'price' => 18000,
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDNY6sjCIoFQf2w6Bi858G4GvHhPsepzQR_CjWXXqp3GTgx6I2Uz0PaJRKdZowv4jyAgT9wiNJqcVzMZg4BrWB4A1J7GYtzqMN1uQcylrS5I4Be84n4d6nsLRhiU-FJQSRzLNn16knDtrGdwrQhVjrlmOdRzaiGceQn1RcwoxUmV1Va2ZosIQVzTjx7qL9xacTMxopIAXRCLVnHBhlbayQalFHSGjgRuEjJl1lF4QjDvsTR7Uaixdb5-g',
                'description' => null,
                'status' => 'tersedia',
            ]
        );

        // 5. Active Shift untuk Kasir
        $activeShift = Shift::firstOrCreate(
            ['user_id' => $cashier->id, 'status' => 'open'],
            [
                'opening_cash' => 500000,
                'cash_sales' => 1200000,
                'expected_cash' => 1700000,
                'opened_at' => Carbon::today()->setTime(8, 0, 0),
                'notes' => 'Shift 1 - Siang',
            ]
        );

        // 6. Transaksi Dummy agar Dashboard & POS memiliki metrik realistis
        $trx1 = Transaction::firstOrCreate(
            ['invoice_number' => 'POS-0024'],
            [
                'user_id' => $cashier->id,
                'shift_id' => $activeShift->id,
                'subtotal' => 82000,
                'tax_amount' => 0,
                'service_charge_amount' => 0,
                'total_amount' => 82000,
                'payment_method' => 'qris',
                'amount_paid' => 82000,
                'change_due' => 0,
                'status' => 'completed',
                'notes' => 'Es Kopi Susu Senja x2',
                'created_at' => Carbon::now()->subMinutes(15),
            ]
        );

        TransactionItem::firstOrCreate(
            ['transaction_id' => $trx1->id, 'product_id' => $prodKopi->id],
            [
                'product_name_snapshot' => $prodKopi->name,
                'unit_price_snapshot' => $prodKopi->price,
                'quantity' => 2,
                'subtotal' => 36000,
                'note' => 'Less sugar',
            ]
        );

        $trx2 = Transaction::firstOrCreate(
            ['invoice_number' => 'POS-0023'],
            [
                'user_id' => $cashier->id,
                'shift_id' => $activeShift->id,
                'subtotal' => 36000,
                'tax_amount' => 0,
                'service_charge_amount' => 0,
                'total_amount' => 36000,
                'payment_method' => 'cash',
                'amount_paid' => 50000,
                'change_due' => 14000,
                'status' => 'completed',
                'notes' => 'Matcha Cream Latte',
                'created_at' => Carbon::now()->subMinutes(30),
            ]
        );

        TransactionItem::firstOrCreate(
            ['transaction_id' => $trx2->id, 'product_id' => $prodMatcha->id],
            [
                'product_name_snapshot' => $prodMatcha->name,
                'unit_price_snapshot' => $prodMatcha->price,
                'quantity' => 1,
                'subtotal' => 22000,
                'note' => 'Es sedikit',
            ]
        );

        $trx3 = Transaction::firstOrCreate(
            ['invoice_number' => 'POS-0022'],
            [
                'user_id' => $cashier->id,
                'shift_id' => $activeShift->id,
                'subtotal' => 58000,
                'tax_amount' => 0,
                'service_charge_amount' => 0,
                'total_amount' => 58000,
                'payment_method' => 'qris',
                'amount_paid' => 58000,
                'change_due' => 0,
                'status' => 'completed',
                'notes' => 'Fries Gurih + Es Kopi',
                'created_at' => Carbon::now()->subMinutes(55),
            ]
        );

        $trx4 = Transaction::firstOrCreate(
            ['invoice_number' => 'POS-0021'],
            [
                'user_id' => $cashier->id,
                'shift_id' => $activeShift->id,
                'subtotal' => 15000,
                'tax_amount' => 0,
                'service_charge_amount' => 0,
                'total_amount' => 15000,
                'payment_method' => 'cash',
                'amount_paid' => 20000,
                'change_due' => 5000,
                'status' => 'completed',
                'notes' => 'Es Teh Manis Jumbo x3',
                'created_at' => Carbon::now()->subHours(2),
            ]
        );
    }
}

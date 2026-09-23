<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2);
            $table->text('image_url')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('tersedia'); // 'tersedia' | 'tidak_tersedia'
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('opening_cash', 14, 2);
            $table->decimal('cash_sales', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->string('status')->default('open'); // 'open' | 'closed'
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained(); // Cashier
            $table->foreignId('shift_id')->constrained();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('service_charge_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->string('payment_method'); // 'cash' | 'qris'
            $table->decimal('amount_paid', 14, 2);
            $table->decimal('change_due', 14, 2)->default(0);
            $table->string('status')->default('completed'); // 'completed' | 'void'
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Transaction Items
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->decimal('unit_price_snapshot', 14, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 14, 2);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // 6. Void Logs
        Schema::create('void_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // User executing void
            $table->text('reason');
            $table->timestamp('void_at')->useCurrent();
            $table->timestamps();
        });

        // 7. Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Foodislice POS UMKM');
            $table->string('store_address')->nullable();
            $table->string('store_phone')->nullable();
            $table->text('store_logo')->nullable();
            $table->string('invoice_prefix')->default('INV');
            $table->string('currency')->default('IDR');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->boolean('tax_enabled')->default(false);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->boolean('service_charge_enabled')->default(false);
            $table->decimal('service_charge_percentage', 5, 2)->default(0);
            $table->boolean('cash_enabled')->default(true);
            $table->boolean('qris_enabled')->default(true);
            $table->timestamps();
        });

        // 8. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->nullOnDelete();
            $table->string('action');
            $table->string('entity')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('void_logs');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};

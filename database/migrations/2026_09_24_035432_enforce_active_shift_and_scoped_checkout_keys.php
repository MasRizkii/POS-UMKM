<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('active_user_id')->nullable()->after('user_id')->constrained('users');
        });

        DB::table('shifts')->where('status', 'open')->update([
            'active_user_id' => DB::raw('user_id'),
        ]);

        if (DB::table('shifts')->whereNotNull('active_user_id')->groupBy('active_user_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Resolve duplicate active shifts before applying the active-shift constraint.');
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->unique('active_user_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_idempotency_key_unique');
            $table->unique(['user_id', 'shift_id', 'idempotency_key'], 'transactions_checkout_idempotency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_checkout_idempotency_unique');
            $table->unique('idempotency_key');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['active_user_id']);
            $table->dropConstrainedForeignId('active_user_id');
        });
    }
};

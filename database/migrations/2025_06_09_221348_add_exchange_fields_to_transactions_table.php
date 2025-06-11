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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('exchange_source')->nullable()->after('transaction_type');
            $table->string('exchange_transaction_id')->nullable()->after('exchange_source');
            $table->timestamp('synced_at')->nullable()->after('exchange_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['exchange_source', 'exchange_transaction_id', 'synced_at']);
        });
    }
};

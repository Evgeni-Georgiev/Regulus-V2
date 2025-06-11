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
            // Add foreign key constraints with cascade deletion
            $table->foreign('portfolio_id')->references('id')->on('portfolios')->cascadeOnDelete();
            $table->foreign('coin_id')->references('id')->on('coins')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the foreign key constraints
            $table->dropForeign(['portfolio_id']);
            $table->dropForeign(['coin_id']);
        });
    }
};

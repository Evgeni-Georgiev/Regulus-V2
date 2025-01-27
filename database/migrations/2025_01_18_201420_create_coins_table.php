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
        Schema::create('coins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('symbol', 20)->unique();
            $table->decimal('price', 20, 2);
            $table->decimal('market_cap', 20, 2);
            $table->decimal('percent_change_1h', 8, 2);
            $table->decimal('percent_change_24h', 8, 2);
            $table->decimal('percent_change_7d', 8, 2);
            $table->decimal('volume_24h', 20, 2);
            $table->index('symbol');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coins');
    }
};

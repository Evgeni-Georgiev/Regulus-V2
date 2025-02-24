<?php

use App\Enums\TransactionTypeEnum;
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
        Schema::create('portfolio_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios')->onDelete('cascade');
            $table->decimal('previous_value', 20, 8)->nullable()->comment('Portfolio value before the change');
            $table->decimal('new_value', 20, 8)->nullable()->comment('Portfolio value after the change');
            $table->enum('change_type', TransactionTypeEnum::values())->nullable()->comment('Type of change (deposit/withdrawal)');
            $table->decimal('change_value', 20, 8)->nullable()->comment('Amount of value based on Portfolio total value was changed');
            $table->timestamp('changed_at')->useCurrent()->nullable()->comment('Timestamp when the change happened');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_histories');
    }
};

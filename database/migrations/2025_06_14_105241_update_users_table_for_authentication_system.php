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
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing name column if it exists
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
            
            // Add first_name and last_name columns
            $table->string('first_name', 50)->after('id');
            $table->string('last_name', 50)->after('first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove first_name and last_name columns
            $table->dropColumn(['first_name', 'last_name']);
            
            // Restore the name column
            $table->string('name')->after('id');
        });
    }
};

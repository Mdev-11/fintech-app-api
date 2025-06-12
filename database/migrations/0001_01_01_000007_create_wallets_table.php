<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // For future wallet settings
            $table->timestamps();
            
            // Add index for faster queries
            $table->index(['user_id', 'created_at']);
        });

        // Move existing wallet balances from users table to wallets table
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('wallet_balance');
        // });
    }

    public function down()
    {
        // Restore wallet_balance column to users table
        // Schema::table('users', function (Blueprint $table) {
        //     $table->decimal('wallet_balance', 10, 2)->default(0);
        // });

        Schema::dropIfExists('wallets');
    }
}; 
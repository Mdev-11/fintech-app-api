<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->nullable()->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->string('type'); // transfer, recharge, withdrawal
            $table->string('status'); // pending, completed, failed
            $table->string('reference')->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For additional transaction data
            $table->timestamps();
            
            // Add cursor pagination index
            $table->index(['created_at', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}; 
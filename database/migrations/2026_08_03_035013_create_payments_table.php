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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Foreign Key
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Payment Details
            $table->string('method'); // KHQR, ABA_PAY, CARD, CASH, COD
            $table->decimal('amount_paid', 10, 2);

            // POS / Cash Handling Details (Optional for cash/COD)
            $table->decimal('tendered_amount', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();

            // Bank / Gateway Transaction ID
            $table->string('transaction_id')->nullable();

            // Status & Timestamp
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

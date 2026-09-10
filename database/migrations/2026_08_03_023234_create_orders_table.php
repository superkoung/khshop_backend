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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Financial Amounts
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2);

            // Delivery Info - Snapshot
            $table->text('shipping_address');
            $table->string('receiver_phone');

            // Order Status
            $table->string('status')->default('pending');
            // pending, processing, shipped, completed, cancelled

            $table->string('order_type')->default('delivery');
            // delivery, pickup

            $table->string('payment_status')->default('unpaid');
            // unpaid, paid, failed, refunded

            // Additional Info
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

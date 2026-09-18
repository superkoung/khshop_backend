<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collection_id')
                  ->constrained('collections')
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collections');
    }
};

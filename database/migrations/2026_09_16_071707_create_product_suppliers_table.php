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
        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (ភ្ជាប់ទៅតារាង products និង suppliers)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                  ->constrained('suppliers')
                  ->cascadeOnDelete();

            $table->string('supplier_sku')->nullable();                 // SKU ដែលប្រើដោយ Supplier
            $table->decimal('cost_price', 12, 2)->default(0.00);        // តម្លៃដើម (ទិញចូល)
            $table->boolean('is_primary')->default(false);              // តើជា Supplier ចម្បងដែរឬទេ?

            $table->timestamps();

            // បង្កើត Unique Index ការពារកុំឱ្យ Duplicate Product និង Supplier ដដែលៗ
            $table->unique(['product_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

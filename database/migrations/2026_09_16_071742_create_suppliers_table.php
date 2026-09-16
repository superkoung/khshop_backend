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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // ឈ្មោះក្រុមហ៊ុន ឬឈ្មោះអ្នកផ្គត់ផ្គង់
            $table->string('contact_name')->nullable();   // ឈ្មោះអ្នកតំណាង/Sales
            $table->string('phone')->nullable();          // លេខទូរស័ព្ទ
            $table->string('email')->nullable();          // អ៊ីមែល
            $table->text('address')->nullable();          // អាសយដ្ឋាន
            $table->boolean('is_active')->default(true);  // ស្ថានភាព (Active/Inactive)
            $table->text('notes')->nullable();            // កំណត់សម្គាល់ផ្សេងៗ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

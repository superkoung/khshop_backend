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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performed_by_user')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete();

        // ធ្វើលើអ្វី & សកម្មភាពអ្វី
            $table->string('table_affected'); // ឧ. 'products'
            $table->string('action_type');     // ឧ. 'CREATE', 'UPDATE', 'DELETE'
            $table->unsignedBigInteger('record_id')->nullable(); // ID នៃ Row ដែលប៉ះពាល់

            // Data Tracking (Optional ប៉ុន្តែល្អខ្លាំងសម្រាប់ Audit)
            $table->json('old_values')->nullable(); // Data ចាស់មុន Edit
            $table->json('new_values')->nullable(); // Data ថ្មីក្រោយ Edit
            $table->string('ip_address', 45)->nullable(); // IP Address របស់អ្នកធ្វើ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

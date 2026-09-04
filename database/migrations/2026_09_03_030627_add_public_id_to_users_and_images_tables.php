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
        // ថែម public_id ទៅតារាង users
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_id')->nullable()->after('avatar_path');
        });

        // ថែម public_id ទៅតារាង images
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('public_id')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};

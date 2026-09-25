<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('public_id')->nullable()->after('image_path');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('public_id')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};

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
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->boolean('apresiasi')->default(false);
            $table->text('apresiasi_catatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn(['apresiasi', 'apresiasi_catatan']);
        });
    }
};

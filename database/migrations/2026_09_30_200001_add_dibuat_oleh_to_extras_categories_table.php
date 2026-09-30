<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras_categories', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('grup')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('extras_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });
    }
};

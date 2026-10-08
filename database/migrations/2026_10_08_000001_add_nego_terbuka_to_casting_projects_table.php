<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', fn (Blueprint $table) => $table->boolean('nego_terbuka')->default(true)->after('is_urgent'));
    }

    public function down(): void
    {
        Schema::table('casting_projects', fn (Blueprint $table) => $table->dropColumn('nego_terbuka'));
    }
};

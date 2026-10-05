<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->text('catatan_penyelesaian')->nullable()->after('alasan_sengketa'));
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('catatan_penyelesaian'));
    }
};

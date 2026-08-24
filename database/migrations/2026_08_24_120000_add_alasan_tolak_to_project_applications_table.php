<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            // Diisi saat Admin reject dini (RF-15 perluasan) ATAU saat CD reject —
            // biar Extras tau alasan konkret, bukan cuma badge "Ditolak" doang.
            $table->text('alasan_tolak')->nullable()->after('bentrok_jadwal_flag');
        });
    }

    public function down(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            $table->dropColumn('alasan_tolak');
        });
    }
};

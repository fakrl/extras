<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            // RF-56: token link publik pendaftaran event. Nullable karena
            // proyek lama (dibuat sebelum fitur ini) tidak punya token — TIDAK
            // di-backfill, link publik memang belum ada buat proyek lama itu.
            $table->string('share_token', 32)->nullable()->unique()->after('client_ph');
        });
    }

    public function down(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BH.3: portofolio beranda dikurasi Admin/SA, nama client default disembunyikan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->boolean('tampil_portofolio')->default(false);
            $table->string('portofolio_judul')->nullable();
            $table->string('portofolio_jenis')->nullable();
            $table->smallInteger('portofolio_tahun')->nullable();
            $table->boolean('tampilkan_nama_client')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropColumn(['tampil_portofolio', 'portofolio_judul', 'portofolio_jenis', 'portofolio_tahun', 'tampilkan_nama_client']);
        });
    }
};

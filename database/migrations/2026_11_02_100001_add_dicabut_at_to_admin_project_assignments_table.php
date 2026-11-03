<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Audit item 4: PIC/Korlap lama yang digeser modal "Pengelola" harus langsung
// kehilangan akses `dikelolaOleh`, tapi baris admin_project_assignments-nya
// (dan StaffPayroll turunannya kalau sudah ada) tidak boleh ikut terhapus.
// status_log tetap murni berjalan/selesai (RF-45/46, dipakai FilterAkun juga),
// jadi penanda "sudah digeser" pakai kolom baru, bukan nilai enum baru.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_project_assignments', function (Blueprint $table) {
            $table->timestamp('dicabut_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('admin_project_assignments', function (Blueprint $table) {
            $table->dropColumn('dicabut_at');
        });
    }
};

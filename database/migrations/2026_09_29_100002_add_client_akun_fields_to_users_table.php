<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BD.1: akun Client dibuat Super Admin, email opsional.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('nama_perusahaan')->nullable()->after('name');
            $table->boolean('wajib_ganti_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nama_perusahaan', 'wajib_ganti_password']);
            $table->string('email')->nullable(false)->change();
        });
    }
};

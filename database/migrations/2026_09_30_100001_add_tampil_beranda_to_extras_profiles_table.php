<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BH.2: izin publik diisi Extras, tampil di beranda diatur Admin/SA.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->boolean('izin_tampil_publik')->default(false);
            $table->boolean('tampil_di_beranda')->default(false);
            $table->timestamp('tampil_di_beranda_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn(['izin_tampil_publik', 'tampil_di_beranda', 'tampil_di_beranda_at']);
        });
    }
};

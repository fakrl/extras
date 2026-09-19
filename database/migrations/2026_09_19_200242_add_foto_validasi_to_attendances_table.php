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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('catatan');
            $table->enum('status_validasi', ['menunggu', 'tervalidasi'])->default('tervalidasi')->after('foto_path');
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete()->after('status_validasi');
            $table->timestamp('divalidasi_at')->nullable()->after('divalidasi_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['divalidasi_oleh']);
            $table->dropColumn(['foto_path', 'status_validasi', 'divalidasi_oleh', 'divalidasi_at']);
        });
    }
};

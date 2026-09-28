<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->text('alasan_sengketa')->nullable()->after('dikonfirmasi_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima', 'disengketakan') DEFAULT 'belum_dibayar'");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('status', ['belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima', 'disengketakan'])->default('belum_dibayar')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima') DEFAULT 'belum_dibayar'");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('status', ['belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima'])->default('belum_dibayar')->change();
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('alasan_sengketa');
        });
    }
};

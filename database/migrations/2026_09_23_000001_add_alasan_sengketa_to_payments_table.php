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
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima') DEFAULT 'belum_dibayar'");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('alasan_sengketa');
        });
    }
};

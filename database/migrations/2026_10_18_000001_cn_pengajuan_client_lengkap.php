<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC CN.1.2: budget proyek total (Client), budget per orang per kelas jadi
// nullable (diisi Admin PIC setelah disetujui), status pengajuan tambah nilai 'perlu_revisi'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->decimal('budget_total', 14, 2)->nullable()->after('kuota');
        });

        Schema::table('casting_project_classes', function (Blueprint $table) {
            $table->decimal('budget_client', 12, 2)->nullable()->change();
        });

        Schema::table('casting_projects', function (Blueprint $table) {
            $table->enum('client_request_status', ['draft', 'menunggu_acc', 'disetujui', 'ditolak', 'perlu_revisi'])
                ->default('disetujui')->change();
        });
    }

    public function down(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->enum('client_request_status', ['draft', 'menunggu_acc', 'disetujui', 'ditolak'])
                ->default('disetujui')->change();
        });

        Schema::table('casting_project_classes', function (Blueprint $table) {
            $table->decimal('budget_client', 12, 2)->nullable(false)->change();
        });

        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropColumn('budget_total');
        });
    }
};

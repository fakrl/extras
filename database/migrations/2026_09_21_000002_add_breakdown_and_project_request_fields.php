<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modul 1 & 2: Tambah kolom di casting_projects (Dua Pintu & Cover)
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->change();
            $table->string('cover_path')->nullable()->after('poster_path');
            $table->enum('client_request_status', ['draft', 'menunggu_acc', 'disetujui', 'ditolak'])
                ->default('disetujui')
                ->after('status');
            $table->foreignId('diajukan_oleh_client_id')->nullable()->constrained('users')->nullOnDelete()->after('client_request_status');
            $table->text('brief_catatan')->nullable()->after('diajukan_oleh_client_id');
        });

        // 2. Modul 1: Breakdown Extras & Callingan di casting_project_classes
        Schema::table('casting_project_classes', function (Blueprint $table) {
            $table->time('jam_callsheet')->nullable()->after('kuota_kelas');
            $table->time('jam_callingan')->nullable()->after('jam_callsheet');
            $table->string('karakter')->nullable()->after('jam_callingan');
            $table->string('keterangan_scene')->nullable()->after('karakter');
            $table->enum('tipe_continuity', ['continuity', 'free'])->default('free')->after('keterangan_scene');
        });

        // 3. Modul 1: Override breakdown per-aplikasi di project_applications
        Schema::table('project_applications', function (Blueprint $table) {
            $table->string('karakter_override')->nullable()->after('fee_final');
            $table->string('scene_override')->nullable()->after('karakter_override');
            $table->time('jam_callingan_override')->nullable()->after('scene_override');
            $table->string('tipe_continuity_override')->nullable()->after('jam_callingan_override');
        });

        // 4. Modul 6: Dual-Model Invoice di tabel invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('template_type')->default('default')->after('pdf_path');
            $table->string('custom_doc_path')->nullable()->after('template_type');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['template_type', 'custom_doc_path']);
        });

        Schema::table('project_applications', function (Blueprint $table) {
            $table->dropColumn(['karakter_override', 'scene_override', 'jam_callingan_override', 'tipe_continuity_override']);
        });

        Schema::table('casting_project_classes', function (Blueprint $table) {
            $table->dropColumn(['jam_callsheet', 'jam_callingan', 'karakter', 'keterangan_scene', 'tipe_continuity']);
        });

        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropForeign(['diajukan_oleh_client_id']);
            $table->dropColumn(['cover_path', 'client_request_status', 'diajukan_oleh_client_id', 'brief_catatan']);
        });
    }
};

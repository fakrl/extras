<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUS = ['diajukan', 'direview_admin', 'nego_fee', 'deal', 'diajukan_ke_client', 'lolos', 'ditolak', 'kontrak_ditandatangani', 'selesai_produksi', 'dibatalkan'];

    public function up(): void
    {
        $this->enum([...self::STATUS, 'diundang']);
        Schema::table('project_applications', fn (Blueprint $table) => $table->timestamp('diundang_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('project_applications', fn (Blueprint $table) => $table->dropColumn('diundang_at'));
        DB::table('project_applications')->where('status_partisipasi', 'diundang')->update(['status_partisipasi' => 'dibatalkan']);
        $this->enum(self::STATUS);
    }

    private function enum(array $nilai): void
    {
        Schema::table('project_applications', fn (Blueprint $table) => $table->enum('status_partisipasi', $nilai)->default('diajukan')->change());
    }
};

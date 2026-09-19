<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->enum('grade_saat_ini', ['A', 'B', 'C'])->nullable()->after('share_token');
            $table->timestamp('grade_diberikan_at')->nullable()->after('grade_saat_ini');
        });
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn(['grade_saat_ini', 'grade_diberikan_at']);
        });
    }
};

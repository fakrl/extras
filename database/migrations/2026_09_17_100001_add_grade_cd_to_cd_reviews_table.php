<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cd_reviews', function (Blueprint $table) {
            $table->enum('grade_cd', ['A', 'B', 'C'])->nullable()->after('keputusan');
        });
    }

    public function down(): void
    {
        Schema::table('cd_reviews', function (Blueprint $table) {
            $table->dropColumn('grade_cd');
        });
    }
};

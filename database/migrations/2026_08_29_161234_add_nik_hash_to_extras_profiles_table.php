<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->string('nik_hash', 64)->nullable()->unique()->after('nik');
        });
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn('nik_hash');
        });
    }
};

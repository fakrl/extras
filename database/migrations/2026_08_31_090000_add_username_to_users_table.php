<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identifier login alternatif selain email + dipakai di tampilan
            // "Alias (@username)" ke Admin/CD. Nullable karena user lama belum
            // punya; unique supaya tidak ada dua akun dengan username sama.
            $table->string('username', 50)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};

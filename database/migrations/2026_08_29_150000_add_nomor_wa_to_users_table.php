<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // RF-37: nomor WA disimpan di users (bukan extras_profiles) supaya
            // reusable lintas role (Admin/CD nanti). Nullable — user existing
            // belum tentu isi. Format disimpan seragam via mutator di model.
            $table->string('nomor_wa')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nomor_wa');
        });
    }
};

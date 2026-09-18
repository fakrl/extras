<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->string('share_token', 32)->nullable()->unique()->after('apresiasi_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};

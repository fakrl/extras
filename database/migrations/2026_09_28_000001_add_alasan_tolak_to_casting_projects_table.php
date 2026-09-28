<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->string('alasan_tolak', 500)->nullable()->after('brief_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropColumn('alasan_tolak');
        });
    }
};

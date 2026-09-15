<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->string('poster_path')->nullable()->after('client_ph');
        });
    }

    public function down(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropColumn('poster_path');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kolom kriteria dari awal json, padahal isinya teks bebas (cast 'string').
// SQLite nggak peduli, MySQL nolak insert teks biasa ke kolom JSON.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_project_classes', function (Blueprint $table) {
            $table->text('kriteria')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('casting_project_classes', function (Blueprint $table) {
            $table->json('kriteria')->nullable()->change();
        });
    }
};

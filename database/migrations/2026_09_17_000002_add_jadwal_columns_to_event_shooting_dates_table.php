<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_shooting_dates', function (Blueprint $table) {
            $table->string('lokasi')->nullable()->after('tanggal');
            $table->time('jam_mulai')->nullable()->after('lokasi');
            $table->time('jam_selesai')->nullable()->after('jam_mulai');
            $table->text('catatan')->nullable()->after('jam_selesai');
            $table->json('panggilan')->nullable()->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('event_shooting_dates', function (Blueprint $table) {
            $table->dropColumn(['lokasi', 'jam_mulai', 'jam_selesai', 'catatan', 'panggilan']);
        });
    }
};

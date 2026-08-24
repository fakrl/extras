<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('channel', ['email', 'whatsapp']); // RF-36/RF-37
            $table->string('jenis'); // hasil_seleksi, reminder_h1, kontrak_siap_ttd, dst
            $table->enum('status', ['terkirim', 'gagal']);
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};

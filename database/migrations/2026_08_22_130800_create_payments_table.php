<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RF-24 / CLAUDE.md §6: status pembayaran SENGAJA terpisah dari
        // status_partisipasi di project_applications — dua lifecycle independen.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima'])->default('belum_dibayar');
            $table->string('bukti_transfer_path')->nullable(); // RF-28, private disk
            $table->timestamp('ditransfer_at')->nullable();
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

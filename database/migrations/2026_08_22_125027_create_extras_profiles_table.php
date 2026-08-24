<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extras_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('nik')->nullable()->unique();
            $table->text('nama_asli')->nullable();
            $table->string('alias')->nullable();

            $table->integer('usia')->nullable();
            $table->string('gender')->nullable();
            $table->integer('tinggi_badan')->nullable();
            $table->string('ukuran_baju')->nullable();
            $table->string('warna_kulit')->nullable();
            $table->text('pengalaman')->nullable();
            $table->string('bahasa')->nullable();

            $table->decimal('rate_card', 12, 2)->nullable();
            $table->string('foto_profil_path')->nullable();
            $table->string('video_profil_path')->nullable();
            $table->text('rekening')->nullable();

            $table->enum('status', ['aktif', 'tidak_aktif', 'melanggar'])->default('aktif');
            $table->unsignedTinyInteger('cancel_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extras_profiles');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casting_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users'); // RF-09: Admin Default pembuat
            $table->string('nama_produksi');
            $table->string('client_ph'); // nama Production House (entitas, tanpa akun)
            $table->date('deadline');
            $table->unsignedInteger('kuota');
            $table->boolean('is_urgent')->default(false); // RF-09: flag "Butuh Dadakan"
            $table->enum('status', ['dibuka', 'ditutup'])->default('dibuka'); // RF-10
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casting_projects');
    }
};

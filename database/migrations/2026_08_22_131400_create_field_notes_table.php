<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('korlap_id')->constrained('users'); // RF-35
            $table->enum('jenis', ['catatan', 'sanksi']);
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_notes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('pdf_path')->nullable(); // RF-25, private disk
            $table->string('ttd_admin_signature_path')->nullable(); // canvas signature image, RF-26
            $table->string('ttd_extras_signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};

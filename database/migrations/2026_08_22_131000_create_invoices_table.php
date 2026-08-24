<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->string('pdf_path')->nullable(); // RF-31, private disk
            $table->string('ttd_admin_signature_path')->nullable();
            $table->string('ttd_cd_signature_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

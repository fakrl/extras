<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_project_assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('nominal_pokok', 12, 2); // disalin dari admin_profiles.honor_nominal saat proyek selesai
            $table->string('pdf_slip_path')->nullable(); // RF-48, private disk
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payrolls');
    }
};

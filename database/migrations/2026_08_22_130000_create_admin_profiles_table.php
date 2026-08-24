<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // RF-41: nominal honor per-event, diset Super Admin saat rekrut sub-admin.
            // Null untuk Super Admin / Admin Default (nggak relevan buat mereka).
            $table->decimal('honor_nominal', 12, 2)->nullable();
            $table->timestamp('honor_updated_at')->nullable();

            // RF-40: jejak siapa (Super Admin) yang nambahin akun Admin ini.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
    }
};

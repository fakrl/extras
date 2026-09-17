<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extras_category_extras_profile', function (Blueprint $table) {
            $table->foreignId('extras_profile_id')->constrained('extras_profiles')->cascadeOnDelete();
            $table->foreignId('extras_category_id')->constrained('extras_categories')->cascadeOnDelete();
            $table->primary(['extras_profile_id', 'extras_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extras_category_extras_profile');
    }
};

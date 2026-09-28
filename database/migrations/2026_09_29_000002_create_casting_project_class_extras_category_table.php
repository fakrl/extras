<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casting_project_class_extras_category', function (Blueprint $table) {
            $table->foreignId('casting_project_class_id')->constrained('casting_project_classes', indexName: 'cpc_extras_category_class_fk')->cascadeOnDelete();
            $table->foreignId('extras_category_id')->constrained('extras_categories', indexName: 'cpc_extras_category_category_fk')->cascadeOnDelete();
            $table->primary(['casting_project_class_id', 'extras_category_id'], 'cpc_extras_category_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casting_project_class_extras_category');
    }
};

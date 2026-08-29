<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            $table->foreignId('casting_project_class_id')->nullable()->after('extras_id')
                ->constrained('casting_project_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('casting_project_class_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('casting_projects')->whereNotNull('diajukan_oleh_client_id')->update(['client_id' => DB::raw('diajukan_oleh_client_id')]);
        DB::table('casting_projects')->whereNull('client_id')->pluck('id')->each(function ($id) {
            $cd = DB::table('cd_project_assignments')->where('casting_project_id', $id)->orderBy('id')->value('cd_user_id');
            if ($cd) {
                DB::table('casting_projects')->where('id', $id)->update(['client_id' => $cd]);
            }
        });

        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('nominal', 15, 2)->nullable();
            $table->string('status_bayar')->default('belum');
            $table->timestamp('dibayar_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['nominal', 'status_bayar', 'dibayar_at']);
        });
        Schema::dropIfExists('project_expenses');
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};

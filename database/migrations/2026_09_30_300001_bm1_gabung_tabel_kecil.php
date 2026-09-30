<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SPEC BM.1: cd_project_assignments → casting_projects.client_id, extras_photos → extras_profiles.foto_tambahan,
// admin_profiles → users.honor_nominal, notifications_log (cuma ditulis, tak pernah dibaca) → status di notifications.data.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('casting_projects')->whereNull('client_id')->pluck('id')->each(function ($id) {
            $client = DB::table('cd_project_assignments')->where('casting_project_id', $id)->orderBy('id')->value('cd_user_id');
            if ($client) {
                DB::table('casting_projects')->where('id', $id)->update(['client_id' => $client]);
            }
        });
        Schema::dropIfExists('cd_project_assignments');

        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->json('foto_tambahan')->nullable();
        });
        DB::table('extras_photos')->orderBy('urutan')->get()->groupBy('extras_profile_id')->each(function ($fotos, $profileId) {
            $slot = $fotos->whereBetween('urutan', [1, 4])->mapWithKeys(fn ($f) => [(int) $f->urutan => $f->path])->all();
            if ($slot) {
                DB::table('extras_profiles')->where('id', $profileId)->update(['foto_tambahan' => json_encode($slot)]);
            }
        });
        Schema::dropIfExists('extras_photos');

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('honor_nominal', 12, 2)->nullable();
        });
        DB::table('admin_profiles')->whereNotNull('honor_nominal')->get(['user_id', 'honor_nominal'])
            ->each(fn ($p) => DB::table('users')->where('id', $p->user_id)->update(['honor_nominal' => $p->honor_nominal]));
        Schema::dropIfExists('admin_profiles');

        Schema::dropIfExists('notifications_log');
    }

    public function down(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('channel', ['email', 'whatsapp']);
            $table->string('jenis');
            $table->enum('status', ['terkirim', 'gagal']);
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('honor_nominal', 12, 2)->nullable();
            $table->timestamp('honor_updated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        DB::table('users')->whereNotNull('honor_nominal')->get(['id', 'honor_nominal'])
            ->each(fn ($u) => DB::table('admin_profiles')->insert(['user_id' => $u->id, 'honor_nominal' => $u->honor_nominal, 'created_at' => now(), 'updated_at' => now()]));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('honor_nominal'));

        Schema::create('extras_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extras_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->string('path');
            $table->timestamps();
            $table->unique(['extras_profile_id', 'urutan']);
        });
        DB::table('extras_profiles')->whereNotNull('foto_tambahan')->get(['id', 'foto_tambahan'])->each(function ($p) {
            foreach (json_decode($p->foto_tambahan, true) ?: [] as $slot => $path) {
                DB::table('extras_photos')->insert(['extras_profile_id' => $p->id, 'urutan' => $slot, 'path' => $path, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        Schema::table('extras_profiles', fn (Blueprint $table) => $table->dropColumn('foto_tambahan'));

        Schema::create('cd_project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cd_user_id')->constrained('users');
            $table->timestamps();
            $table->unique(['casting_project_id', 'cd_user_id']);
        });
        DB::table('casting_projects')->whereNotNull('client_id')->get(['id', 'client_id'])
            ->each(fn ($p) => DB::table('cd_project_assignments')->insert(['casting_project_id' => $p->id, 'cd_user_id' => $p->client_id, 'created_at' => now(), 'updated_at' => now()]));
    }
};

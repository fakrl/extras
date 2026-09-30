<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// SPEC BM.2: kolom dobel / sisa lama. Data dipindah dulu, baru di-drop.
// Dipertahankan: casting_projects.kuota (pengajuan Client belum punya peran), *_override (form breakdown Admin),
// share_token (link publik masih token), extras_profiles.berat_badan (angka baru BJ).
return new class extends Migration
{
    private const STATUS = ['diajukan', 'direview_admin', 'nego_fee', 'deal', 'diajukan_ke_cd', 'lolos', 'ditolak', 'kontrak_ditandatangani', 'selesai_produksi', 'dibatalkan'];

    public function up(): void
    {
        DB::table('casting_projects')->whereNull('link_grup')->whereNotNull('wa_group_link')->update(['link_grup' => DB::raw('wa_group_link')]);
        DB::table('casting_projects')->whereNull('poster_path')->whereNotNull('cover_path')->update(['poster_path' => DB::raw('cover_path')]);
        DB::table('casting_projects')->whereNotNull('cover_path')->whereColumn('cover_path', '!=', 'poster_path')->get(['id', 'cover_path', 'admin_id'])
            ->each(function ($p) {
                if (! Storage::disk('public')->exists($p->cover_path)) {
                    return;
                }
                $path = "project-attachments/{$p->id}/".basename($p->cover_path);
                Storage::disk('local')->put($path, Storage::disk('public')->get($p->cover_path));
                DB::table('project_attachments')->insert([
                    'casting_project_id' => $p->id, 'uploaded_by' => $p->admin_id, 'nama_asli' => 'cover-'.basename($p->cover_path),
                    'path' => $path, 'mime' => Storage::disk('local')->mimeType($path) ?: 'image/jpeg', 'ukuran' => Storage::disk('local')->size($path),
                    'keterangan' => 'Cover naskah / moodboard', 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        $this->tautkanClient();

        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diajukan_oleh_client_id');
        });
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->dropColumn(['wa_group_link', 'client_ph', 'cover_path']);
        });

        (require __DIR__.'/2026_09_30_200002_add_riwayat_pengalaman_to_extras_profiles_table.php')->up();
        (require __DIR__.'/2026_09_30_200004_warna_kulit_jadi_tag.php')->up();
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn(['pengalaman', 'cancel_count', 'warna_kulit']);
        });

        DB::table('casting_project_classes')->whereNotNull('karakter')->whereColumn('karakter', '!=', 'nama_kelas')->get(['id', 'karakter'])
            ->each(fn ($k) => DB::table('project_applications')->where('casting_project_class_id', $k->id)->whereNull('karakter_override')
                ->update(['karakter_override' => $k->karakter]));
        Schema::table('casting_project_classes', fn (Blueprint $table) => $table->dropColumn('karakter'));

        Schema::table('cd_reviews', fn (Blueprint $table) => $table->dropColumn('bulk_batch_id'));

        DB::table('project_applications')->where('status_partisipasi', 'direview_cd')->update(['status_partisipasi' => 'diajukan_ke_cd']);
        Schema::table('project_applications', function (Blueprint $table) {
            $table->enum('status_partisipasi', self::STATUS)->default('diajukan')->change();
        });
    }

    /** client_ph → users.nama_perusahaan; proyek tanpa Client ditautkan ke akun yang namanya cocok, atau dibuatkan akun Client (nonaktif). */
    private function tautkanClient(): void
    {
        DB::table('casting_projects')->whereNotNull('client_id')->whereNotNull('client_ph')->where('client_ph', '!=', '')
            ->orderByDesc('id')->get(['client_id', 'client_ph'])->unique('client_id')
            ->each(fn ($p) => DB::table('users')->where('id', $p->client_id)->where('name', '!=', $p->client_ph)
                ->where(fn ($q) => $q->whereNull('nama_perusahaan')->orWhere('nama_perusahaan', ''))
                ->update(['nama_perusahaan' => $p->client_ph]));

        DB::table('casting_projects')->whereNull('client_id')->whereNotNull('client_ph')->where('client_ph', '!=', '')->get(['id', 'client_ph'])
            ->each(function ($p) {
                $nama = trim($p->client_ph);
                $client = DB::table('users')->where('role', 'client')->whereNull('deleted_at')
                    ->where(fn ($q) => $q->whereRaw('lower(nama_perusahaan) = ?', [mb_strtolower($nama)])->orWhereRaw('lower(name) = ?', [mb_strtolower($nama)]))
                    ->value('id');

                if (! $client) {
                    $dasar = Str::limit(Str::slug($nama, '_') ?: 'client', 40, '');
                    $username = $dasar;
                    for ($i = 2; DB::table('users')->where('username', $username)->exists(); $i++) {
                        $username = $dasar.'_'.$i;
                    }
                    $client = DB::table('users')->insertGetId([
                        'name' => $nama, 'nama_perusahaan' => $nama, 'username' => $username, 'email' => null,
                        'password' => Hash::make(Str::random(32)), 'wajib_ganti_password' => true,
                        'role' => 'client', 'status' => 'nonaktif', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                DB::table('casting_projects')->where('id', $p->id)->update(['client_id' => $client]);
            });
    }

    public function down(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            $table->enum('status_partisipasi', [...array_slice(self::STATUS, 0, 5), 'direview_cd', ...array_slice(self::STATUS, 5)])->default('diajukan')->change();
        });
        Schema::table('cd_reviews', fn (Blueprint $table) => $table->uuid('bulk_batch_id')->nullable());
        Schema::table('casting_project_classes', fn (Blueprint $table) => $table->string('karakter')->nullable());
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->string('warna_kulit')->nullable();
            $table->text('pengalaman')->nullable();
            $table->unsignedTinyInteger('cancel_count')->default(0);
        });
        Schema::table('casting_projects', function (Blueprint $table) {
            $table->string('client_ph')->nullable();
            $table->string('wa_group_link')->nullable();
            $table->string('cover_path')->nullable();
            $table->foreignId('diajukan_oleh_client_id')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('casting_projects')->whereNotNull('client_id')->get(['id', 'client_id'])->each(function ($p) {
            $c = DB::table('users')->where('id', $p->client_id)->first(['name', 'nama_perusahaan']);
            DB::table('casting_projects')->where('id', $p->id)->update(['client_ph' => $c?->nama_perusahaan ?: $c?->name]);
        });
    }
};

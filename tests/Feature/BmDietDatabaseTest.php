<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** SPEC BM.1: tabel kecil digabung, kontrak fitur tetap sama. */
class BmDietDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_lama_sudah_tidak_ada(): void
    {
        foreach (['cd_project_assignments', 'extras_photos', 'admin_profiles', 'notifications_log'] as $tabel) {
            $this->assertFalse(Schema::hasTable($tabel), $tabel);
        }
        $this->assertTrue(Schema::hasColumn('extras_profiles', 'foto_tambahan'));
        $this->assertTrue(Schema::hasColumn('users', 'honor_nominal'));
    }

    public function test_bm2_kolom_dobel_sudah_di_drop(): void
    {
        foreach ([
            'casting_projects' => ['wa_group_link', 'diajukan_oleh_client_id', 'client_ph', 'cover_path'],
            'extras_profiles' => ['pengalaman', 'cancel_count', 'warna_kulit'],
            'casting_project_classes' => ['karakter'],
            'cd_reviews' => ['bulk_batch_id'],
        ] as $tabel => $kolom) {
            $this->assertFalse(Schema::hasColumns($tabel, $kolom), $tabel);
        }
        $this->assertTrue(Schema::hasColumns('casting_projects', ['kuota', 'link_grup', 'poster_path', 'share_token']));
        $this->assertTrue(Schema::hasColumns('extras_profiles', ['berat_badan', 'share_token']));
        $this->assertTrue(Schema::hasColumns('project_applications', ['karakter_override', 'scene_override', 'jam_callingan_override', 'tipe_continuity_override']));
    }

    public function test_hitungan_batal_mendadak_dari_cancellations(): void
    {
        $extras = ExtrasProfile::factory()->create();
        foreach ([[true, 'extras'], [true, 'extras'], [false, 'extras'], [true, 'admin']] as [$mendadak, $oleh]) {
            ProjectApplication::create(['casting_project_id' => CastingProject::factory()->create()->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'dibatalkan'])
                ->cancellations()->create(['dibatalkan_oleh' => $oleh, 'alasan' => 'x', 'is_mendadak' => $mendadak]);
        }

        $this->assertSame(2, $extras->fresh()->cancel_count);
        $this->assertSame(2, ExtrasProfile::withBatalMendadak()->find($extras->id)->cancel_count);
    }

    public function test_foto_tambahan_upload_ganti_stream_hapus_per_slot(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('extras.profile.foto-tambahan.ajax', 3), ['foto' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk()->assertJsonPath('url', fn ($u) => str_contains($u, '/foto-tambahan/'.$profile->id.'/3'));
        $lama = $profile->fresh()->foto_tambahan[3];
        Storage::disk('local')->assertExists($lama);

        $this->actingAs($user)->post(route('extras.profile.foto-tambahan.ajax', 3), ['foto' => UploadedFile::fake()->image('b.jpg')])->assertOk();
        $this->actingAs($user)->post(route('extras.profile.foto-tambahan.ajax', 1), ['foto' => UploadedFile::fake()->image('c.jpg')])->assertOk();
        Storage::disk('local')->assertMissing($lama);
        $this->assertSame([1, 3], $profile->fresh()->fotoTambahan()->keys()->all());

        $this->actingAs($user)->get(route('extras.media.foto-tambahan', [$profile, 3]))->assertOk();
        $this->actingAs($user)->get(route('extras.media.foto-tambahan', [$profile, 2]))->assertNotFound();

        $this->actingAs($user)->delete(route('extras.profile.foto-tambahan.hapus', 3))->assertRedirect();
        $this->assertSame([1], $profile->fresh()->fotoTambahan()->keys()->all());
        $this->actingAs($user)->get(route('extras.media.foto-tambahan', [$profile, 3]))->assertNotFound();

        $this->actingAs($user)->post(route('extras.profile.foto-tambahan.ajax', 5), ['foto' => UploadedFile::fake()->image('d.jpg')])->assertStatus(422);
    }

    public function test_reminder_h1_tidak_dobel_untuk_proyek_dan_tanggal_sama(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);
        $project = CastingProject::factory()->create();
        $project->shootingDates()->create(['tanggal' => now()->addDay()]);
        $extras = ExtrasProfile::factory()->create(['user_id' => User::factory()->create(['role' => 'extras', 'nomor_wa' => '0812345678'])->id]);
        ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'lolos']);

        Artisan::call('reminder:h1-shooting');
        Artisan::call('reminder:h1-shooting');

        $this->assertCount(1, $this->notifikasi($extras->user_id, 'reminder_h1'));
        Http::assertSentCount(1);
    }

    public function test_reminder_h3_ke_akun_client_proyek(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);
        $client = User::factory()->create(['role' => 'client', 'nomor_wa' => '0812000000']);
        $project = CastingProject::factory()->create(['client_id' => $client->id]);
        $project->shootingDates()->create(['tanggal' => now()->addDays(3)]);
        ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => ExtrasProfile::factory()->create()->id, 'status_partisipasi' => 'diajukan_ke_cd']);

        Artisan::call('reminder:h3-pilih-extras');
        Artisan::call('reminder:input-jadwal');
        Artisan::call('reminder:h3-pilih-extras');

        $this->assertNotifikasi($client->id, 'reminder_h3_pilih_extras', ['wa' => 'terkirim']);
        $this->assertCount(1, $this->notifikasi($client->id, 'reminder_h3_pilih_extras'));
        $this->assertCount(1, $this->notifikasi($client->id, 'reminder_input_jadwal'));
    }

    public function test_detail_akun_tampilkan_status_wa_notifikasi(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras', 'nomor_wa' => null]);
        $extras->kabari('Hasil Seleksi', 'Maaf belum lolos', jenis: 'hasil_seleksi', wa: 'pesan');

        $this->actingAs($sa)->get(route('super-admin.admins.show', $extras))
            ->assertOk()->assertSee('Notifikasi Terakhir')->assertSee('data-status-wa="gagal"', false);
    }
}

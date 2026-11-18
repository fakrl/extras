<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\CastingProjectClass;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoResizeThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private function dimensi(string $isi): array
    {
        $info = getimagesizefromstring($isi);

        return [$info[0], $info[1]];
    }

    public function test_upload_foto_besar_diresize_dan_bikin_thumbnail(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'nama_asli' => 'Nama Asli']);

        $this->actingAs($user)->post(route('extras.profile.foto'), [
            'foto' => UploadedFile::fake()->image('foto.jpg', 2000, 1500),
        ])->assertRedirect();

        $profile->refresh();
        $path = $profile->foto_profil_path;
        $thumbPath = ExtrasProfile::thumbPath($path);

        Storage::disk('local')->assertExists($path);
        Storage::disk('local')->assertExists($thumbPath);

        [$lebarFull, $tinggiFull] = $this->dimensi(Storage::disk('local')->get($path));
        [$lebarThumb, $tinggiThumb] = $this->dimensi(Storage::disk('local')->get($thumbPath));

        $this->assertSame(1200, max($lebarFull, $tinggiFull));
        $this->assertSame(400, max($lebarThumb, $tinggiThumb));
        $this->assertLessThan(Storage::disk('local')->size($path), Storage::disk('local')->size($thumbPath));
    }

    public function test_foto_stream_thumb_dan_full_punya_cache_control_private(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'nama_asli' => 'Nama Asli']);
        $this->actingAs($user)->post(route('extras.profile.foto'), [
            'foto' => UploadedFile::fake()->image('foto.jpg', 2000, 1500),
        ]);
        $profile->refresh();

        $this->actingAs($user)->get(route('extras.media.foto', $profile))
            ->assertOk()->assertHeader('Cache-Control', 'max-age=21600, private');
        $this->actingAs($user)->get(route('extras.media.foto', $profile).'?thumb=1')
            ->assertOk()->assertHeader('Cache-Control', 'max-age=21600, private');
    }

    public function test_foto_lama_tanpa_thumbnail_digenerate_lazy_saat_diakses(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'extras']);
        $path = 'extras/lama/foto-lama.jpg';
        Storage::disk('local')->put($path, (string) UploadedFile::fake()->image('x.jpg', 800, 600)->get());
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'nama_asli' => 'Nama Asli', 'foto_profil_path' => $path]);

        $thumbPath = ExtrasProfile::thumbPath($path);
        Storage::disk('local')->assertMissing($thumbPath);

        $this->actingAs($user)->get(route('extras.media.foto', $profile).'?thumb=1')->assertOk();

        Storage::disk('local')->assertExists($thumbPath);
        [$lebar, $tinggi] = $this->dimensi(Storage::disk('local')->get($thumbPath));
        $this->assertSame(400, max($lebar, $tinggi));
    }

    public function test_kartu_lineup_merender_url_thumbnail_bukan_foto_asli(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $kelas = CastingProjectClass::create(['casting_project_id' => $project->id, 'nama_kelas' => 'Umum', 'budget_client' => 100000, 'kuota_kelas' => 5]);
        $extrasUser = User::factory()->create(['role' => 'extras', 'username' => 'kandidat_foto']);
        $profile = ExtrasProfile::create(['user_id' => $extrasUser->id, 'nama_asli' => 'Nama Asli', 'foto_profil_path' => 'extras/x/foto/a.jpg']);
        Storage::disk('local')->put('extras/x/foto/a.jpg', (string) UploadedFile::fake()->image('x.jpg', 800, 600)->get());
        ProjectApplication::create(['casting_project_id' => $project->id, 'casting_project_class_id' => $kelas->id, 'extras_id' => $profile->id, 'status_partisipasi' => 'nego_fee']);

        $this->actingAs($admin)->get(route('admin.projects.applicants', $project))
            ->assertOk()
            ->assertSee(route('extras.media.foto', $profile).'?thumb=1', false);
    }
}

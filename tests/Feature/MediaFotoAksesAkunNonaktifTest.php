<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Item 1: pastikan gate 404 akun nonaktif (commit 71327e2) cuma berlaku di
 * rute publik berbasis token, bukan rute internal Admin/SA/Client/Korlap -
 * mereka tetap harus bisa lihat foto extras nonaktif.
 */
class MediaFotoAksesAkunNonaktifTest extends TestCase
{
    use RefreshDatabase;

    private function profilNonaktif(): ExtrasProfile
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'extras', 'status' => 'nonaktif']);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'foto_profil_path' => 'foto/'.$user->id.'.jpg']);
        Storage::disk('local')->put($profile->foto_profil_path, 'isi-foto-palsu');

        return $profile->fresh();
    }

    public function test_admin_tetap_bisa_lihat_foto_extras_nonaktif(): void
    {
        $profile = $this->profilNonaktif();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('extras.media.foto', $profile))->assertOk();
    }

    public function test_super_admin_tetap_bisa_lihat_foto_extras_nonaktif(): void
    {
        $profile = $this->profilNonaktif();
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->get(route('extras.media.foto', $profile))->assertOk();
    }

    public function test_client_yang_berhak_tetap_bisa_lihat_foto_extras_nonaktif(): void
    {
        $profile = $this->profilNonaktif();
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create(['client_id' => $client->id]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $profile->id,
            'status_partisipasi' => 'diajukan_ke_client',
        ]);

        $this->actingAs($client)->get(route('extras.media.foto', $profile))->assertOk();
    }

    public function test_korlap_yang_ditugaskan_tetap_bisa_lihat_selfie_absensi_extras_nonaktif(): void
    {
        Storage::fake('local');
        $profile = $this->profilNonaktif();
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = CastingProject::factory()->create();
        $project->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $korlap->id]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $profile->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
        ]);
        $shootingDate = EventShootingDate::create(['casting_project_id' => $project->id, 'tanggal' => now()->addDay()]);

        $attendance = Attendance::create([
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
            'dicatat_oleh' => $korlap->id,
            'foto_path' => 'absensi/selfie.jpg',
        ]);
        Storage::disk('local')->put('absensi/selfie.jpg', 'isi-selfie-palsu');

        $this->actingAs($korlap)->get(route('admin.absensi.foto', $attendance))->assertOk();
    }

    public function test_anonymous_akses_rute_internal_diarahkan_login_bukan_404(): void
    {
        $profile = $this->profilNonaktif();

        $this->get(route('extras.media.foto', $profile))->assertRedirect(route('login'));
    }

    public function test_anonymous_akses_rute_publik_akun_nonaktif_tetap_404(): void
    {
        $profile = $this->profilNonaktif();
        $profile->generateShareToken();

        $this->get(route('public.extras.foto', $profile->fresh()->share_token))->assertNotFound();
    }
}

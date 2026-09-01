<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FieldNoteTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extras = ExtrasProfile::factory()->create(['alias' => 'Alias Test']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);
    }

    public function test_korlap_bisa_tambah_catatan(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $application = $this->buatAplikasi();

        $response = $this->actingAs($korlap)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'catatan',
            'isi' => 'Datang tepat waktu, sikap baik.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('field_notes', [
            'project_application_id' => $application->id,
            'korlap_id' => $korlap->id,
            'jenis' => 'catatan',
            'isi' => 'Datang tepat waktu, sikap baik.',
        ]);
    }

    public function test_admin_default_bisa_tambah_sanksi(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi();

        $response = $this->actingAs($admin)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'sanksi',
            'isi' => 'Terlambat 2 jam tanpa kabar.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('field_notes', [
            'project_application_id' => $application->id,
            'korlap_id' => $admin->id,
            'jenis' => 'sanksi',
        ]);
    }

    #[DataProvider('peranTanpaAksesProvider')]
    public function test_role_lain_ditolak_403(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $application = $this->buatAplikasi();

        $response = $this->actingAs($user)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'catatan',
            'isi' => 'Tes akses',
        ]);

        $response->assertStatus(403);
    }

    public static function peranTanpaAksesProvider(): array
    {
        return [
            ['extras'],
            ['casting_director'],
            ['admin_talco'],
            ['admin_sosmed'],
        ];
    }
}

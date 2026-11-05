<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** SPEC.md Bagian CY.3: halaman Syarat & Ketentuan + kalimat pengungkapan potongan 25% ke Extras. */
class CySyaratKetentuanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_syarat_ketentuan_bisa_diakses_dan_memuat_aturan_potongan(): void
    {
        $response = $this->get(route('syarat-ketentuan'));

        $response->assertOk();
        $response->assertSee('25%', false);
        $response->assertSee('500.000', false);
        $response->assertSee('375.000', false);
        $response->assertDontSee('7 hari kerja');
        $response->assertDontSee('hari kerja');
    }

    public function test_halaman_nego_fee_extras_memuat_kalimat_potongan_agensi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $project = CastingProject::create(['admin_id' => $admin->id, 'nama_produksi' => 'Proyek Nego', 'deadline' => now()->addDays(7), 'kuota' => 5]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'nego_fee',
        ]);

        $response = $this->actingAs($extrasUser)->get(route('extras.negotiations.show', $application));

        $response->assertOk();
        $response->assertSee('Fee yang kamu terima sudah setelah potongan jasa agensi 25%.');
    }

    public function test_halaman_detail_lowongan_extras_memuat_kalimat_potongan_agensi(): void
    {
        $project = CastingProject::factory()->create(['nama_produksi' => 'Proyek Lowongan CY']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $response = $this->actingAs($extrasUser)->get(route('extras.projects.show', $project));

        $response->assertOk();
        $response->assertSee('Fee yang kamu terima sudah setelah potongan jasa agensi 25%.');
    }
}

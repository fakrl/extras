<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi audit full-codebase (30 Agu 2026): budget_client (fee dari
 * client) sempat tampil ke Extras di halaman lowongan — pelanggaran
 * tembok visibilitas CLAUDE.md §5 (budget_client cuma boleh Admin).
 */
class ExtrasBudgetVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyekDenganKelas(): CastingProject
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Rahasia', 'client_ph' => 'PH X',
            'status' => 'dibuka', 'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu', 'budget_client' => 999999, 'kuota_kelas' => 3,
        ]);

        return $project;
    }

    public function test_index_lowongan_tidak_menampilkan_budget_client(): void
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias']);
        $this->buatProyekDenganKelas();

        $response = $this->actingAs($extrasUser)->get('/extras/lowongan');

        $response->assertOk();
        $response->assertDontSee('999.999');
        $response->assertDontSee('999999');
    }

    public function test_show_lowongan_tidak_menampilkan_budget_client(): void
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias']);
        $project = $this->buatProyekDenganKelas();

        $response = $this->actingAs($extrasUser)->get('/extras/lowongan/'.$project->id);

        $response->assertOk();
        $response->assertDontSee('999.999');
        $response->assertDontSee('999999');
    }
}

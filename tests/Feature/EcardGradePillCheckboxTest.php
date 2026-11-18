<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcardGradePillCheckboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_lineup_dengan_grade_dan_checkbox_tidak_tumpuk(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Umum', 'budget_client' => 100000, 'kuota_kelas' => 5]);
        $extras = ExtrasProfile::create([
            'user_id' => User::factory()->create(['role' => 'extras', 'username' => 'kandidat_grade'])->id,
            'nama_asli' => 'KTP kandidat_grade',
        ]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $kelas->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'nego_fee',
            'grade' => 'A',
        ]);

        // kartu ini memang merender checkbox (xcard-ck) dan grade pill (ecard-grade-pill) sekaligus
        $this->actingAs($admin)->get(route('admin.projects.applicants', $project))
            ->assertOk()
            ->assertSee('class="xcard-ck"', false)
            ->assertSee('ecard-grade-pill', false);

        // aturan CSS yang menggeser grade-pill ke bawah checkbox biar nggak tumpuk di sudut yang sama
        $css = file_get_contents(resource_path('views/partials/theme-style.blade.php'));
        $this->assertStringContainsString('.xcard-ph:has(.xcard-ck) .ecard-grade-pill', $css);
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantGradeFilterTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(CastingProject $project, ?string $grade): ProjectApplication
    {
        $extras = ExtrasProfile::factory()->create(['alias' => 'Alias '.$grade]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'direview_admin',
            'grade' => $grade,
        ]);
    }

    public function test_filter_grade_a_hanya_menampilkan_pendaftar_grade_a(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);

        $appA = $this->buatAplikasi($project, 'A');
        $appB = $this->buatAplikasi($project, 'B');
        $appNull = $this->buatAplikasi($project, null);

        $response = $this->actingAs($admin)->get('/admin/projects/'.$project->id.'/applicants?grade=A');

        $response->assertOk();
        $response->assertViewHas('applicants', function ($applicants) use ($appA, $appB, $appNull) {
            return $applicants->pluck('id')->contains($appA->id)
                && ! $applicants->pluck('id')->contains($appB->id)
                && ! $applicants->pluck('id')->contains($appNull->id);
        });
    }

    public function test_filter_belum_dinilai_hanya_menampilkan_grade_null(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $appA = $this->buatAplikasi($project, 'A');
        $appNull = $this->buatAplikasi($project, null);

        $response = $this->actingAs($admin)->get('/admin/projects/'.$project->id.'/applicants?grade=belum');

        $response->assertOk();
        $response->assertViewHas('applicants', function ($applicants) use ($appA, $appNull) {
            return $applicants->pluck('id')->contains($appNull->id)
                && ! $applicants->pluck('id')->contains($appA->id);
        });
    }

    public function test_tanpa_filter_menampilkan_semua_pendaftar(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $this->buatAplikasi($project, 'A');
        $this->buatAplikasi($project, 'B');
        $this->buatAplikasi($project, null);

        $response = $this->actingAs($admin)->get('/admin/projects/'.$project->id.'/applicants');

        $response->assertOk();
        $response->assertViewHas('applicants', fn ($applicants) => $applicants->count() === 3);
    }
}

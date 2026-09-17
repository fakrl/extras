<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeCdKategoriTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Test Proyek',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function buatApplicationDiajukanKeCd(CastingProject $project): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan_ke_cd',
            'fee_final' => 200000,
        ]);
    }

    public function test_approve_dengan_grade_cd_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);
        $application = $this->buatApplicationDiajukanKeCd($project);

        $response = $this->actingAs($cd)->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
            'grade_cd' => 'A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cd_reviews', [
            'project_application_id' => $application->id,
            'keputusan' => 'approve',
            'grade_cd' => 'A',
        ]);
    }

    public function test_approve_tanpa_grade_cd_gagal_validasi(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);
        $application = $this->buatApplicationDiajukanKeCd($project);

        $response = $this->actingAs($cd)->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ]);

        $response->assertSessionHasErrors('grade_cd');
    }

    public function test_reject_tanpa_grade_cd_berhasil(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);
        $application = $this->buatApplicationDiajukanKeCd($project);

        $response = $this->actingAs($cd)->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'reject',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cd_reviews', [
            'project_application_id' => $application->id,
            'keputusan' => 'reject',
            'grade_cd' => null,
        ]);
    }

    public function test_assign_kategori_many_to_many_sync(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $kat = ExtrasCategory::create(['nama' => 'Dewasa']);

        $response = $this->actingAs($admin)->patch(route('admin.users.kategori', $extrasUser), [
            'kategori_ids' => [$kat->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue($extras->categories()->where('extras_categories.id', $kat->id)->exists());
    }

    public function test_filter_rekap_by_kategori(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $kat = ExtrasCategory::create(['nama' => 'Remaja']);

        $extrasUserA = User::factory()->create(['role' => 'extras']);
        $extrasA = ExtrasProfile::create(['user_id' => $extrasUserA->id]);
        $extrasA->categories()->attach($kat->id);

        $extrasUserB = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUserB->id]);

        $response = $this->actingAs($admin)->get(route('admin.recap.index', ['kategori_id' => $kat->id]));

        $response->assertOk();
    }
}

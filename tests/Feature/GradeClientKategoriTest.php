<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeClientKategoriTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Test Proyek',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function buatApplicationDiajukanKeClient(CastingProject $project): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan_ke_client',
            'fee_final' => 200000,
        ]);
    }

    public function test_approve_dengan_grade_client_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);
        $application = $this->buatApplicationDiajukanKeClient($project);

        $response = $this->actingAs($klien)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
            'grade_client' => 'A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('client_reviews', [
            'project_application_id' => $application->id,
            'keputusan' => 'approve',
            'grade_client' => 'A',
        ]);
    }

    public function test_approve_tanpa_grade_client_gagal_validasi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);
        $application = $this->buatApplicationDiajukanKeClient($project);

        $response = $this->actingAs($klien)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ]);

        $response->assertSessionHasErrors('grade_client');
    }

    public function test_reject_tanpa_grade_client_berhasil(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);
        $application = $this->buatApplicationDiajukanKeClient($project);

        $response = $this->actingAs($klien)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'reject',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('client_reviews', [
            'project_application_id' => $application->id,
            'keputusan' => 'reject',
            'grade_client' => null,
        ]);
    }

    public function test_assign_kategori_many_to_many_sync(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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
        $admin = User::factory()->create(['role' => 'admin']);
        $kat = ExtrasCategory::create(['nama' => 'Remaja']);

        $extrasUserA = User::factory()->create(['role' => 'extras']);
        $extrasA = ExtrasProfile::create(['user_id' => $extrasUserA->id]);
        $extrasA->categories()->attach($kat->id);

        $extrasUserB = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUserB->id]);

        $this->actingAs($admin)->get(route('admin.recap.index', ['kategori_id' => $kat->id]))
            ->assertRedirect(route('admin.akun.extras', ['tag' => [$kat->id]]));
        $this->actingAs($admin)->get(route('admin.akun.extras', ['tag' => [$kat->id]]))
            ->assertOk()
            ->assertViewHas('extras', fn ($p) => $p->pluck('id')->all() === [$extrasUserA->id]);
    }
}

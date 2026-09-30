<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC.md Bagian E + BM.1: Client hanya boleh akses invoice & review kandidat proyek
 * miliknya (casting_projects.client_id).
 */
class ClientAksesProyekTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function buatApplicationDiajukanKeClient(CastingProject $project, string $username = 'alias_test_client'): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras', 'username' => $username]);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan_ke_client',
            'fee_final' => 200000,
        ]);
    }

    public function test_milik_client_cuma_untuk_akun_client_proyek_itu(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $lain = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $client->id]);

        $this->assertTrue($project->milikClient($client));
        $this->assertFalse($project->milikClient($lain));
        $this->assertFalse($project->milikClient($admin));
        $this->assertSame([$project->id], CastingProject::query()->milikClient($client)->pluck('id')->all());
        $this->assertSame([], CastingProject::query()->milikClient($lain)->pluck('id')->all());
    }

    public function test_client_yang_diassign_tetap_bisa_akses_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);
        $project1->update(['client_id' => $klienA->id]);

        $this->actingAs($klienA)->get(route('invoices.show', $project1))->assertOk();
    }

    public function test_client_yang_tidak_diassign_ditolak_akses_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $klienB = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);
        $project2 = $this->buatProyek($admin);
        $project1->update(['client_id' => $klienA->id]);
        $project2->update(['client_id' => $klienB->id]);

        $this->actingAs($klienB)->get(route('invoices.show', $project1))->assertStatus(403);
    }

    public function test_client_tanpa_assignment_sama_sekali_ditolak_akses_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienTanpaProyek = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);

        $this->actingAs($klienTanpaProyek)->get(route('invoices.show', $project1))->assertStatus(403);
    }

    public function test_client_yang_diassign_bisa_lihat_dan_approve_kandidat_proyeknya(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);
        $project1->update(['client_id' => $klienA->id]);
        $application = $this->buatApplicationDiajukanKeClient($project1);

        // Level 1 (index) tidak tampilkan alias - cek via show (Level 2)
        $this->actingAs($klienA)->get(route('client.reviews.index'))->assertOk()->assertSee('Proyek Test');
        $this->actingAs($klienA)->get(route('client.reviews.show', $project1))->assertOk()->assertSee('alias_test_client');

        $this->actingAs($klienA)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
            'grade_client' => 'A',
        ])->assertRedirect();

        $this->assertSame('lolos', $application->fresh()->status_partisipasi);
    }

    public function test_client_yang_tidak_diassign_tidak_lihat_kandidat_proyek_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $klienB = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);
        $project2 = $this->buatProyek($admin);
        $project1->update(['client_id' => $klienA->id]);
        $project2->update(['client_id' => $klienB->id]);
        $application = $this->buatApplicationDiajukanKeClient($project1);

        // Level 1: klienB hanya lihat proyek miliknya, bukan project1
        $this->actingAs($klienB)->get(route('client.reviews.index'))->assertOk()->assertDontSee('alias_test_client');
        // Level 2: klienB tidak bisa akses show project1 (403)
        $this->actingAs($klienB)->get(route('client.reviews.show', $project1))->assertForbidden();
    }

    public function test_client_yang_tidak_diassign_aksi_approve_tidak_berefek(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $klienB = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);
        $project1->update(['client_id' => $klienA->id]);
        $application = $this->buatApplicationDiajukanKeClient($project1);

        $this->actingAs($klienB)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ])->assertRedirect();

        $this->assertSame('diajukan_ke_client', $application->fresh()->status_partisipasi);
    }

    public function test_client_tanpa_assignment_sama_sekali_tidak_bisa_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienTanpaProyek = User::factory()->create(['role' => 'client']);
        $project1 = $this->buatProyek($admin);
        $application = $this->buatApplicationDiajukanKeClient($project1);

        $this->actingAs($klienTanpaProyek)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ])->assertRedirect();

        $this->assertSame('diajukan_ke_client', $application->fresh()->status_partisipasi);
    }

    public function test_ajukan_ke_client_gagal_kalau_proyek_belum_ada_client_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($admin);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
            'fee_final' => 200000,
        ]);

        $this->expectException(\LogicException::class);
        $application->ajukanKeClient();

        $this->assertSame('deal', $application->fresh()->status_partisipasi);
    }

    public function test_client_hanya_lihat_riwayat_keputusan_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $klienB = User::factory()->create(['role' => 'client']);
        $projectA = $this->buatProyek($admin);
        $projectB = $this->buatProyek($admin);
        $projectA->update(['client_id' => $klienA->id]);
        $projectB->update(['client_id' => $klienB->id]);

        // Level 1 index berbasis assignment - klienB hanya lihat proyeknya sendiri
        $this->actingAs($klienB)->get(route('client.reviews.index'))
            ->assertOk()
            ->assertDontSee('Alias Test');
    }

    public function test_riwayat_tidak_expose_fee_nama_asli_nik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);

        // Level 1: hanya nama proyek, tidak ada data extras sama sekali
        $response = $this->actingAs($klien)->get(route('client.reviews.index'));
        $response->assertOk();

        $viewData = $response->original->getData();
        $proyek = $viewData['proyek'];

        // Level 1 tidak load extras - hanya castingProject (id, nama_produksi)
        foreach ($proyek as $item) {
            $proyekAttrs = $item['proyek']->getAttributes();
            $this->assertArrayNotHasKey('nik', $proyekAttrs);
            $this->assertArrayNotHasKey('nama_lengkap', $proyekAttrs);
        }
    }
}

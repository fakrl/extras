<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validasi keputusan Client (approve/reject kandidat, ReviewController::review)
 * dan pengajuan proyek Client (ProjectRequestController::store) yang belum dites.
 */
class ClientKeputusanDanPengajuanValidasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(User $client, string $status = 'diajukan_ke_client'): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'client_id' => $client->id, 'nama_produksi' => 'Proyek Review',
            'deadline' => now()->addDays(7), 'kuota' => 5, 'client_request_status' => 'disetujui',
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ]);
    }

    public function test_approve_wajib_isi_grade_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $application = $this->buatAplikasi($client);

        $this->actingAs($client)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ])->assertSessionHasErrors('grade_client');

        $this->assertSame('diajukan_ke_client', $application->fresh()->status_partisipasi);
    }

    public function test_approve_grade_client_harus_a_b_atau_c(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $application = $this->buatAplikasi($client);

        $this->actingAs($client)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
            'grade_client' => 'D',
        ])->assertSessionHasErrors('grade_client');
    }

    public function test_reject_tidak_wajib_grade_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $application = $this->buatAplikasi($client);

        $this->actingAs($client)->post(route('client.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'reject',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('ditolak', $application->fresh()->status_partisipasi);
    }

    /**
     * review() tidak punya abort_unless kepemilikan per-aplikasi (beda dari show()/
     * method lain di controller yang sama, yang abort 403) - ID milik client lain
     * cuma DIAM-DIAM dikecualikan lewat whereHas('castingProject', milikClient()),
     * bukan 403/422. Tes ini mengunci perilaku NYATA saat ini; tidak diubah jadi
     * 403 karena "diam-diam skip ID yang tidak relevan di aksi massal" adalah pola
     * UX yang wajar (anti stale-page), bukan gap teknis yang jelas salah.
     */
    public function test_application_id_milik_client_lain_diam_diam_diabaikan(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientLain = User::factory()->create(['role' => 'client']);
        $milikSendiri = $this->buatAplikasi($client);
        $milikOrangLain = $this->buatAplikasi($clientLain);

        $response = $this->actingAs($client)->post(route('client.reviews.review'), [
            'application_ids' => [$milikSendiri->id, $milikOrangLain->id],
            'keputusan' => 'approve',
            'grade_client' => 'A',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionHas('status', '1 kandidat berhasil disetujui.');
        $this->assertSame('lolos', $milikSendiri->fresh()->status_partisipasi);
        $this->assertSame('diajukan_ke_client', $milikOrangLain->fresh()->status_partisipasi);
    }

    private function dataPengajuan(array $override = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Validasi',
            'deadline' => today()->addDays(10)->format('Y-m-d'),
            'budget_total' => 10000000,
            'brief_catatan' => 'Brief lengkap.',
            'kelas' => [['nama_kelas' => 'Warga', 'kuota_kelas' => 5, 'kriteria' => 'Dewasa']],
            'tanggal_shooting' => [['tanggal' => today()->addDays(12)->format('Y-m-d'), 'lokasi' => 'Jakarta']],
        ], $override);
    }

    public function test_budget_total_negatif_ditolak(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataPengajuan(['budget_total' => -1000]))
            ->assertSessionHasErrors('budget_total');
        $this->assertSame(0, CastingProject::count());
    }

    public function test_budget_total_non_numeric_ditolak(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataPengajuan(['budget_total' => 'banyak']))
            ->assertSessionHasErrors('budget_total');
    }

    public function test_tanggal_shooting_sama_dengan_deadline_ditolak(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $deadline = today()->addDays(5);

        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataPengajuan([
            'deadline' => $deadline->format('Y-m-d'),
            'tanggal_shooting' => [['tanggal' => $deadline->format('Y-m-d'), 'lokasi' => 'Jakarta']],
        ]))->assertSessionHasErrors('tanggal_shooting.0.tanggal');
    }
}

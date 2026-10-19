<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use App\Services\KeuanganService;
use App\Support\AdminRingkasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC CN: pengajuan Client lengkap (peran, tanggal+lokasi, budget total) sebelum
 * sampai ke Super Admin; SA tinggal Setujui (pilih PIC) / Minta revisi / Tolak.
 */
class CnPengajuanClientLengkapTest extends TestCase
{
    use RefreshDatabase;

    private function dataForm(array $override = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek CN Test',
            'deadline' => today()->addDays(10)->format('Y-m-d'),
            'budget_total' => 10000000,
            'brief_catatan' => 'Brief lengkap.',
            'kelas' => [['nama_kelas' => 'Warga', 'kuota_kelas' => 5, 'kriteria' => 'Dewasa']],
            'tanggal_shooting' => [['tanggal' => today()->addDays(12)->format('Y-m-d'), 'lokasi' => 'Jakarta']],
        ], $override);
    }

    public function test_form_wajib_peran_tanggal_dan_budget(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm(['kelas' => []]))
            ->assertSessionHasErrors('kelas');
        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm(['tanggal_shooting' => []]))
            ->assertSessionHasErrors('tanggal_shooting');
        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm(['budget_total' => 0]))
            ->assertSessionHasErrors('budget_total');
        // tanggal shooting wajib setelah deadline
        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm([
            'tanggal_shooting' => [['tanggal' => today()->addDays(1)->format('Y-m-d')]],
        ]))->assertSessionHasErrors('tanggal_shooting.0.tanggal');

        $this->assertSame(0, CastingProject::count());
    }

    public function test_budget_per_orang_kosong_setelah_pengajuan(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm())
            ->assertRedirect(route('client.dashboard'));

        $project = CastingProject::where('nama_produksi', 'Proyek CN Test')->firstOrFail();
        $this->assertNull($project->classes->first()->budget_client);
        $this->assertSame(5, $project->kuota);
    }

    public function test_minta_revisi_lalu_client_ubah_dan_ajukan_ulang(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm());
        $project = CastingProject::where('nama_produksi', 'Proyek CN Test')->firstOrFail();

        $this->actingAs($sa)->patch(route('super-admin.projects.acc', $project))->assertSessionHasErrors();
        $this->actingAs($sa)->patch(route('super-admin.projects.minta-revisi', $project), [])
            ->assertSessionHasErrors('alasan_tolak');

        $this->actingAs($sa)->patch(route('super-admin.projects.minta-revisi', $project), [
            'alasan_tolak' => 'Lokasi belum jelas, mohon lengkapi.',
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('perlu_revisi', $project->client_request_status);
        $this->assertSame('Lokasi belum jelas, mohon lengkapi.', $project->alasan_tolak);
        $this->assertStringContainsString('Lokasi belum jelas', $client->notifications()->first()?->data['pesan']);

        // Client lain tidak bisa akses halaman ubah
        $clientLain = User::factory()->create(['role' => 'client']);
        $this->actingAs($clientLain)->get(route('client.projects.request.edit', $project))->assertNotFound();

        $this->actingAs($client)->get(route('client.projects.request.edit', $project))
            ->assertOk()->assertSee('Lokasi belum jelas, mohon lengkapi.');

        $this->actingAs($client)->put(route('client.projects.request.update', $project), $this->dataForm([
            'nama_produksi' => 'Proyek CN Test Revisi',
            'tanggal_shooting' => [['tanggal' => today()->addDays(15)->format('Y-m-d'), 'lokasi' => 'Jakarta Selatan']],
        ]))->assertRedirect(route('client.dashboard'));

        $project->refresh();
        $this->assertSame('menunggu_acc', $project->client_request_status);
        $this->assertSame('Proyek CN Test Revisi', $project->nama_produksi);
        $this->assertSame('Jakarta Selatan', $project->shootingDates->first()->lokasi);
    }

    public function test_setujui_wajib_admin_pic_dan_buat_penugasan_korlap(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $this->actingAs($client)->post(route('client.projects.request.store'), $this->dataForm());
        $project = CastingProject::where('nama_produksi', 'Proyek CN Test')->firstOrFail();

        $this->actingAs($sa)->patch(route('super-admin.projects.acc', $project), [
            'admin_id' => $admin->id, 'korlap_id' => $korlap->id,
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('disetujui', $project->client_request_status);
        $this->assertSame($admin->id, $project->admin_id);
        $this->assertSame('ditutup', $project->status);
        $this->assertTrue($project->adminAssignments()->where('user_id', $admin->id)->exists());
        $this->assertTrue($project->adminAssignments()->where('user_id', $korlap->id)->exists());
    }

    public function test_buka_lowongan_diblokir_sampai_semua_budget_per_orang_terisi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Budget Test',
            'client_request_status' => 'disetujui', 'status' => 'ditutup',
            'deadline' => today()->addDays(5), 'kuota' => 10, 'budget_total' => 2000000,
        ]);
        $kelasA = $project->classes()->create(['nama_kelas' => 'A', 'kuota_kelas' => 5, 'budget_client' => 100000]);
        $kelasB = $project->classes()->create(['nama_kelas' => 'B', 'kuota_kelas' => 5]);

        $this->actingAs($admin)->patch(route('admin.projects.toggle-status', $project))
            ->assertSessionHas('error');
        $this->assertSame('ditutup', $project->fresh()->status);

        $kelasB->update(['budget_client' => 150000]);
        $this->actingAs($admin)->patch(route('admin.projects.toggle-status', $project))
            ->assertSessionHas('status');
        $this->assertSame('dibuka', $project->fresh()->status);
    }

    public function test_peringatan_alokasi_melebihi_budget_total_tidak_blocking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Alokasi Lebih',
            'client_request_status' => 'disetujui', 'status' => 'ditutup',
            'deadline' => today()->addDays(5), 'kuota' => 5, 'budget_total' => 100000,
        ]);
        $project->classes()->create(['nama_kelas' => 'A', 'kuota_kelas' => 5, 'budget_client' => 100000]);

        $this->actingAs($admin)->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('Total alokasi melebihi budget proyek');
    }

    public function test_atur_budget_masuk_perlu_tindakan_admin_pic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Butuh Budget',
            'client_request_status' => 'disetujui', 'status' => 'ditutup',
            'deadline' => today()->addDays(5), 'kuota' => 5, 'budget_total' => 100000,
        ]);
        $project->classes()->create(['nama_kelas' => 'A', 'kuota_kelas' => 5]);

        $this->assertSame(1, AdminRingkasan::untuk($admin)['atur_budget']['jumlah']);
        $this->assertSame(0, AdminRingkasan::untuk($lain)['atur_budget']['jumlah']);
    }

    public function test_budget_total_tidak_bocor_ke_admin_yang_bukan_pic(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $pic->id, 'nama_produksi' => 'Proyek Budget Rahasia',
            'client_request_status' => 'disetujui', 'status' => 'ditutup',
            'deadline' => today()->addDays(5), 'kuota' => 5, 'budget_total' => 7000000,
        ]);
        $project->classes()->create(['nama_kelas' => 'A', 'kuota_kelas' => 5, 'budget_client' => 100000]);
        $project->shootingDates()->create(['tanggal' => today()->addDays(10)]);

        $this->actingAs($lain)->get(route('admin.projects.edit', $project))
            ->assertOk()->assertDontSee('7.000.000');

        $this->actingAs($lain)->get(route('admin.projects.show', [$project, 'tab' => 'keuangan']))
            ->assertOk()->assertDontSee('7.000.000');

        $this->actingAs($pic)->get(route('admin.projects.edit', $project))
            ->assertOk()->assertSee('7.000.000');
    }

    public function test_usulan_invoice_pakai_budget_total_bila_terisi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Invoice CN',
            'client_request_status' => 'disetujui', 'status' => 'ditutup',
            'deadline' => today()->addDays(5), 'kuota' => 5, 'budget_total' => 7000000,
        ]);
        $project->classes()->create(['nama_kelas' => 'A', 'kuota_kelas' => 5, 'budget_client' => 100000]);

        $this->assertSame(7000000.0, (new KeuanganService)->nilaiInvoice($project));

        $project->update(['budget_total' => null]);
        $this->assertSame(500000.0, (new KeuanganService)->nilaiInvoice($project->fresh()));
    }
}

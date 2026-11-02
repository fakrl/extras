<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeEditClientPicTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Edit',
            'deadline' => now()->addDays(14)->toDateString(),
            'kuota' => 10,
            'tanggal_shooting' => [now()->addDays(20)->toDateString()],
            'kelas' => [['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2]],
        ], $overrides);
    }

    public function test_ganti_client_dan_pic_buat_assignment_log_dan_nama_client_ikut(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $adminBaru = User::factory()->create(['role' => 'admin', 'name' => 'Admin Baru']);
        $lama = User::factory()->create(['role' => 'client', 'name' => 'Client Lama', 'nama_perusahaan' => 'PH Lama']);
        $baru = User::factory()->create(['role' => 'client', 'name' => 'Client Baru', 'nama_perusahaan' => 'PH Baru']);
        $project = CastingProject::factory()->create(['client_id' => $lama->id]);
        $project->update(['client_id' => $lama->id]);

        $this->actingAs($sa)->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('data-cari-select="client_id"', false)
            ->assertSee('client-baru-dialog')
            ->assertSee('admin-baru-dialog')
            ->assertSee('Admin baru')
            ->assertSee('Client baru');

        // CM.3: godmode SA dicabut untuk aksi tulis, edit proyek kini wajib lewat mode Monitoring Admin.
        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->patch(route('admin.projects.update', $project), $this->payload([
            'admin_id' => $adminBaru->id, 'client_id' => $baru->id,
        ]))->assertRedirect(route('admin.projects.index'));

        $project->refresh();
        $this->assertSame([$adminBaru->id, $baru->id, 'PH Baru'], [$project->admin_id, $project->client_id, $project->namaClient()]);
        $this->assertSame($baru->id, $project->fresh()->client_id);
        $log = ActivityLog::where('action', 'UPDATE_PROJECT_PIC')->sole();
        $this->assertStringContainsString('Client Client Lama → Client Baru', $log->description);
        $this->assertStringContainsString('→ Admin Baru', $log->description);
    }

    public function test_proyek_tanpa_client_wajib_pilih_saat_edit_dan_muncul_di_badge_serta_perlu_tindakan(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::factory()->create(['client_id' => null, 'nama_produksi' => 'Proyek Yatim']);
        CastingProject::factory()->create(['client_id' => User::factory()->create(['role' => 'client'])->id, 'nama_produksi' => 'Proyek Lengkap']);

        // CM.3: godmode SA dicabut untuk aksi tulis, edit proyek kini wajib lewat mode Monitoring Admin.
        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->patch(route('admin.projects.update', $project), $this->payload(['admin_id' => $project->admin_id]))
            ->assertSessionHasErrors(['client_id' => 'Pilih akun Client proyek ini dulu (proyek lama belum punya Client).']);

        // CR.2: baris "Butuh keputusan Anda" tautan aksi langsung (bukan lagi link ke daftar ber-filter).
        $this->actingAs($sa)->get(route('super-admin.dashboard'))
            ->assertSee('PIC/Client belum diisi')->assertSee(route('admin.projects.edit', $project), false);

        $this->actingAs($sa)->get(route('admin.projects.index', ['tanpa_client' => 1]))
            ->assertSee('Proyek Yatim')->assertSee('Tanpa PIC/Client')->assertDontSee('Proyek Lengkap');
    }

    public function test_super_admin_wajib_masuk_mode_admin_untuk_update_proyek(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create(['admin_id' => null, 'client_id' => null]);

        $this->actingAs($sa)->patch(route('admin.projects.update', $project), $this->payload(['nama_produksi' => 'Tanpa Mode']))->assertForbidden();
        $this->assertNotSame('Tanpa Mode', $project->fresh()->nama_produksi);

        $response = $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->patch(route('admin.projects.update', $project), $this->payload([
            'nama_produksi' => 'Proyek Diupdate SA',
            'admin_id' => $admin->id,
            'client_id' => $client->id,
            'kelas' => [
                [
                    'nama_kelas' => 'Peran Utama',
                    'budget_client' => 500000,
                    'kuota_kelas' => 3,
                ],
            ],
        ]));

        $response->assertRedirect(route('admin.projects.index'));
        $fresh = $project->fresh();
        $this->assertSame('Proyek Diupdate SA', $fresh->nama_produksi);
        $this->assertSame($admin->id, $fresh->admin_id);
        $this->assertSame($client->id, $fresh->client_id);
    }
}

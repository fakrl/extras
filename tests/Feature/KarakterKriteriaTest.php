<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\CastingProjectClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarakterKriteriaTest extends TestCase
{
    use RefreshDatabase;

    private function adminPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7)->toDateString(),
            'kuota' => 5,
            'tanggal_shooting' => [now()->addDays(10)->toDateString()],
            'kelas' => [
                ['nama_kelas' => 'Ibu-ibu', 'budget_client' => 400000, 'kuota_kelas' => 3],
            ],
        ], $overrides);
    }

    // L.1 — kriteria tersimpan saat store
    public function test_kriteria_tersimpan_saat_buat_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($admin)->post('/admin/projects', $this->adminPayload([
            'kelas' => [
                ['nama_kelas' => 'Ibu-ibu', 'budget_client' => 400000, 'kuota_kelas' => 3, 'kriteria' => 'wanita 25-35 th, ekspresi sedih'],
            ],
        ]))->assertRedirect(route('admin.projects.index'));

        $kelas = CastingProjectClass::first();
        $this->assertSame('wanita 25-35 th, ekspresi sedih', $kelas->kriteria);
    }

    // L.1 — kriteria nullable
    public function test_kriteria_opsional(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($admin)->post('/admin/projects', $this->adminPayload())
            ->assertRedirect(route('admin.projects.index'));

        $this->assertNull(CastingProjectClass::first()->kriteria);
    }

    // L.1 — kriteria max 500 karakter
    public function test_kriteria_max_500_karakter(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($admin)->post('/admin/projects', $this->adminPayload([
            'kelas' => [
                ['nama_kelas' => 'Ibu-ibu', 'budget_client' => 400000, 'kuota_kelas' => 3, 'kriteria' => str_repeat('a', 501)],
            ],
        ]))->assertSessionHasErrors('kelas.0.kriteria');
    }

    // L.2 — link_grup tersimpan saat store
    public function test_link_grup_tersimpan_saat_buat_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($admin)->post('/admin/projects', $this->adminPayload([
            'link_grup' => 'https://chat.whatsapp.com/koordinasi123',
        ]))->assertRedirect(route('admin.projects.index'));

        $this->assertSame('https://chat.whatsapp.com/koordinasi123', CastingProject::first()->link_grup);
    }

    // L.2 — link_grup tidak wajib
    public function test_link_grup_opsional(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($admin)->post('/admin/projects', $this->adminPayload())
            ->assertRedirect(route('admin.projects.index'));

        $this->assertNull(CastingProject::first()->link_grup);
    }

    // L.2 — link_grup harus URL valid
    public function test_link_grup_invalid_url_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($admin)->post('/admin/projects', $this->adminPayload([
            'link_grup' => 'bukan-url',
        ]))->assertSessionHasErrors('link_grup');
    }

    // L.2 — link_grup TIDAK muncul di model saat null
    public function test_link_grup_null_saat_tidak_diisi(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $this->assertNull($project->link_grup);
    }

    // L.2 — link_grup tersimpan di model
    public function test_link_grup_tersimpan_di_model(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::factory()->create([
            'admin_id' => $admin->id,
            'link_grup' => 'https://t.me/koordinasi_jbtb',
        ]);

        $this->assertSame('https://t.me/koordinasi_jbtb', $project->fresh()->link_grup);
    }

    // L.2 — verifikasi logika kondisi tampil link_grup: status lolos tidak masuk whitelist, status kontrak_ditandatangani masuk
    public function test_link_grup_hanya_tampil_saat_kontrak_ditandatangani(): void
    {
        $statusLolos = 'lolos';
        $statusTtd = 'kontrak_ditandatangani';
        $statusSelesai = 'selesai_produksi';
        $whitelist = ['kontrak_ditandatangani', 'selesai_produksi'];

        $this->assertFalse(in_array($statusLolos, $whitelist, true));
        $this->assertTrue(in_array($statusTtd, $whitelist, true));
        $this->assertTrue(in_array($statusSelesai, $whitelist, true));
    }
}

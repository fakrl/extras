<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeKuotaAntrianTest extends TestCase
{
    use RefreshDatabase;

    private CastingProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Proyek Antrian',
            'client_ph' => 'PH',
            'deadline' => now()->addDays(7),
            'kuota' => 10,
            'status' => 'dibuka',
        ]);
        $this->project->classes()->create(['nama_kelas' => 'Ibu-ibu', 'budget_client' => 1, 'kuota_kelas' => 2]);
        $this->project->classes()->create(['nama_kelas' => 'Bapak-bapak', 'budget_client' => 1, 'kuota_kelas' => 1]);
    }

    private function extras(): User
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        return $u;
    }

    private function daftar(User $u, string $peran)
    {
        return $this->actingAs($u)->post("/extras/lowongan/{$this->project->id}/daftar", [
            'casting_project_class_id' => $this->project->classes()->where('nama_kelas', $peran)->value('id'),
        ]);
    }

    public function test_orang_ketiga_ditolak_lalu_bisa_daftar_setelah_ada_yang_ditolak(): void
    {
        $this->daftar($this->extras(), 'Ibu-ibu')->assertSessionHasNoErrors();
        $this->daftar($this->extras(), 'Ibu-ibu');

        $ketiga = $this->extras();
        $this->daftar($ketiga, 'Ibu-ibu')->assertSessionHas('error', fn ($m) => str_contains($m, 'Kuota peran ini sedang penuh'));
        $this->assertSame(2, $this->project->applications()->count());

        $this->actingAs($ketiga)->get("/extras/lowongan/{$this->project->id}")
            ->assertOk()->assertSee('Penuh')->assertSee('Slot bisa terbuka lagi');

        $this->project->applications()->first()->update(['status_partisipasi' => 'ditolak']);

        $this->daftar($ketiga, 'Ibu-ibu')->assertRedirect(route('extras.dashboard'));
        $this->assertSame(3, $this->project->applications()->count());
    }

    public function test_peran_lain_tetap_bisa_dan_kuota_penuh_hanya_kalau_semua_peran_penuh(): void
    {
        $this->daftar($this->extras(), 'Ibu-ibu');
        $this->daftar($this->extras(), 'Ibu-ibu');
        $this->assertFalse($this->project->fresh()->kuotaPenuh());

        $this->daftar($this->extras(), 'Bapak-bapak')->assertRedirect(route('extras.dashboard'));
        $this->assertTrue($this->project->fresh()->kuotaPenuh());
        $this->assertFalse($this->project->fresh()->menerimaPendaftaran());
    }
}

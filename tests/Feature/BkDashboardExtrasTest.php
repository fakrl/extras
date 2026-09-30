<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BkDashboardExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function extras(): User
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        return $u;
    }

    private function daftar(User $u, string $nama, string $status, ?int $hari = null)
    {
        $p = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => $nama, 'deadline' => now()->addDays(30), 'kuota' => 10, 'status' => 'dibuka',
        ]);
        if ($hari !== null) {
            $p->shootingDates()->create(['tanggal' => now()->addDays($hari)->toDateString()]);
        }

        return $p->applications()->create(['extras_id' => $u->extrasProfile->id, 'status_partisipasi' => $status]);
    }

    public function test_perlu_tindakan_cuma_teks_link_ke_kartu_tombol_di_kartu(): void
    {
        $u = $this->extras();
        $app = $this->daftar($u, 'Iklan Minuman Segar', 'nego_fee', 10);

        $html = $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Negosiasi fee <em>Iklan Minuman Segar</em> menunggu balasanmu', false)
            ->assertSee('href="#pendaftaran-'.$app->id.'"', false)
            ->assertSee('id="pendaftaran-'.$app->id.'"', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, route('extras.negotiations.show', $app)));
        $perlu = substr($html, strpos($html, 'data-dash="perlu-tindakan"'), strpos($html, 'data-dash="pendaftaran"') - strpos($html, 'data-dash="perlu-tindakan"'));
        $this->assertStringNotContainsString('btn', $perlu);
    }

    public function test_pendaftaran_urut_tanggal_terdekat_dan_riwayat_diringkas(): void
    {
        $u = $this->extras();
        $this->daftar($u, 'Jauh', 'diajukan', 20);
        $this->daftar($u, 'Tanpa Tanggal', 'diajukan');
        $this->daftar($u, 'Dekat', 'deal', 3);
        $this->daftar($u, 'Sudah Selesai', 'selesai_produksi', 1);
        $this->daftar($u, 'Kena Tolak', 'ditolak', 2);
        $this->daftar($u, 'Batal', 'dibatalkan', 4);

        $html = $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertSeeInOrder(['data-dash="pendaftaran"', 'Dekat', 'Jauh', 'Tanpa Tanggal', 'Riwayat (3)', 'data-dash="casting-call"'], false)
            ->getContent();

        $riwayat = substr($html, strpos($html, 'data-dash="riwayat"'), strpos($html, '</details>') - strpos($html, 'data-dash="riwayat"'));
        foreach (['Sudah Selesai', 'Kena Tolak', 'Batal'] as $nama) {
            $this->assertStringContainsString($nama, $riwayat);
        }
        $this->assertStringNotContainsString('Jauh', $riwayat);
        $this->assertSame(1, substr_count($html, '>Sudah Selesai<'));
    }
}

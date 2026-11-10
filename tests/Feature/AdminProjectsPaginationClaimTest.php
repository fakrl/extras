<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproduksi klaim: dengan 15+ proyek, GET /admin/projects?urut=shooting&semua=1&per=12&page=2
 * harus menampilkan baris, dan angka tab harus sama dengan jumlah baris yang bisa dipaginasi.
 */
class AdminProjectsPaginationClaimTest extends TestCase
{
    use RefreshDatabase;

    private function seedProjects(User $admin): void
    {
        foreach (range(1, 16) as $i) {
            $p = CastingProject::factory()->create([
                'admin_id' => $admin->id,
                'nama_produksi' => "Proyek Seed {$i}",
                'client_request_status' => 'disetujui',
            ]);
            if ($i % 2 === 0) {
                $p->shootingDates()->create(['tanggal' => today()->addDays($i)]);
            }
        }
    }

    private function cekUrl(User $actor): void
    {
        $r = $this->actingAs($actor)
            ->get('/admin/projects?urut=shooting&semua=1&per=12&page=2')
            ->assertOk();

        $html = $r->getContent();
        $adaBaris = str_contains($html, 'Proyek Seed');
        $totalDb = (int) CastingProject::count();
        $tabCocok = str_contains($html, "Semua ({$totalDb})");

        fwrite(STDERR, sprintf(
            "\n[claim-repro] role=%s page2_ada_baris=%s total_db=%d tab_semua_cocok=%s\n",
            $actor->role, $adaBaris ? 'ya' : 'tidak', $totalDb, $tabCocok ? 'ya' : 'tidak'
        ));

        $this->assertTrue($adaBaris, 'Halaman 2 seharusnya menampilkan baris proyek.');
        $this->assertTrue($tabCocok, 'Angka tab "Semua" seharusnya sama dengan total proyek di DB.');
    }

    public function test_klaim_sebagai_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seedProjects($admin);
        $this->cekUrl($admin);
    }

    public function test_klaim_sebagai_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seedProjects($admin);
        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->cekUrl($sa);
    }
}

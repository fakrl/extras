<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BG.8: kartu Status Proyek jadi tab + daftar 5 proyek per tahap.
 */
class BgStatusProyekTabTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-15 10:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    private function proyek(string $nama, array $tanggal, string $req = 'disetujui'): CastingProject
    {
        $p = CastingProject::factory()->create(['nama_produksi' => $nama, 'client_request_status' => $req]);
        foreach ($tanggal as $t) {
            $p->shootingDates()->create(['tanggal' => $t]);
        }

        return $p;
    }

    private function panel(string $html, string $tahap): string
    {
        $awal = strpos($html, 'id="panel-'.$tahap.'"');

        return substr($html, $awal, strpos($html, 'class="sa-lihat-semua"', $awal) - $awal);
    }

    public function test_empat_panel_isi_sesuai_tahap_maks_lima_dan_default_berjalan(): void
    {
        foreach (range(1, 7) as $i) {
            $this->travel(1)->minutes();
            $this->proyek("Jalan $i", ['2026-06-10', '2026-06-2'.$i]);
            $this->proyek("Datang $i", ['2026-06-2'.$i]);
            $this->proyek("Beres $i", ['2026-06-0'.$i]);
            $this->proyek("Ajuan $i", [], 'menunggu_acc');
        }
        $this->proyek('Jalan Luar Periode', ['2026-05-01', '2026-07-20']);
        $this->proyek('Datang Luar Periode', ['2026-07-20']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertOk()
            ->assertDontSee('5 Proyek Berjalan Teratas')
            ->assertSee('role="tablist"', false);
        $html = $r->getContent();

        $this->assertSame('berjalan', $r->viewData('tabAwal'));
        $this->assertStringContainsString('id="tab-berjalan" aria-controls="panel-berjalan" aria-selected="true"', $html);
        $this->assertStringContainsString('id="tab-mendatang" aria-controls="panel-mendatang" aria-selected="false"', $html);

        foreach (['menunggu_acc' => 'Ajuan', 'mendatang' => 'Datang', 'berjalan' => 'Jalan', 'selesai' => 'Beres'] as $tahap => $awalan) {
            $panel = $this->panel($html, $tahap);
            $this->assertSame(5, substr_count($panel, 'class="dash-row"'), $tahap);
            $this->assertSame(5, substr_count($panel, "<strong>$awalan "), $tahap);
            $this->assertStringNotContainsString('Luar Periode', $panel, $tahap);
            $this->assertSame($tahap !== 'berjalan', str_contains(strtok($panel, '>'), 'hidden'), $tahap);
            $this->assertSame(7, $r->viewData('statusProyek')[$tahap], $tahap);
        }

        $urutan = fn ($tahap) => preg_match_all('/<strong>\w+ (\d)<\/strong>/', $this->panel($html, $tahap), $m) ? $m[1] : [];
        $this->assertSame(['1', '2', '3', '4', '5'], $urutan('mendatang'));
        $this->assertSame(['1', '2', '3', '4', '5'], $urutan('berjalan'));
        $this->assertSame(['7', '6', '5', '4', '3'], $urutan('selesai'));
        $this->assertSame(['7', '6', '5', '4', '3'], $urutan('menunggu_acc'));

        $lihat = e(route('admin.projects.index', ['tahap' => 'selesai', 'dari' => '2026-06-01', 'sampai' => '2026-06-30']));
        $r->assertSee('href="'.$lihat.'" class="sa-lihat-semua">Lihat semua Selesai', false)
            ->assertSee('href="'.e(route('admin.projects.index', ['tahap' => 'menunggu_acc'])).'" class="sa-lihat-semua"', false);
    }

    public function test_default_mendatang_kalau_berjalan_kosong_lalu_berjalan_kalau_semua_kosong(): void
    {
        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))
            ->assertViewHas('tabAwal', 'berjalan')->assertSee('Tidak ada proyek berjalan dalam periode ini.');

        $this->proyek('Ajuan', [], 'menunggu_acc');
        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertViewHas('tabAwal', 'menunggu_acc');

        $this->proyek('Datang', ['2026-06-20']);
        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertViewHas('tabAwal', 'mendatang');
    }
}

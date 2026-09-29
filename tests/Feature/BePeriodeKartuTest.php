<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * BE.2: kartu status dashboard SA & daftar proyek pakai periode yang sama.
 */
class BePeriodeKartuTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-15 10:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);

        $data = [
            ['menunggu_acc', []], ['menunggu_acc', ['2025-01-01']],
            ['disetujui', ['2026-06-20']], ['disetujui', ['2026-07-20']], ['disetujui', []],
            ['disetujui', ['2026-06-14', '2026-06-16']], ['disetujui', ['2026-05-30', '2026-07-02']],
            ['disetujui', ['2026-06-10']], ['disetujui', ['2026-06-02', '2026-06-03']], ['disetujui', ['2025-01-10']],
            ['disetujui', ['2026-05-31', '2026-06-01']], ['ditolak', ['2026-06-25']],
        ];
        foreach ($data as [$req, $tanggal]) {
            $p = CastingProject::factory()->create(['client_request_status' => $req]);
            foreach ($tanggal as $t) {
                $p->shootingDates()->create(['tanggal' => $t]);
            }
        }
        foreach (range(1, 22) as $i) {
            CastingProject::factory()->create(['client_request_status' => 'disetujui'])->shootingDates()->create(['tanggal' => '2026-06-2'.($i % 10)]);
        }
    }

    public static function periode(): array
    {
        return [
            'bulan ini' => [[]],
            'custom' => [['dari' => '2026-06-01', 'sampai' => '2026-06-15']],
            'custom terbalik' => [['dari' => '2026-07-31', 'sampai' => '2026-05-31']],
            'tahun' => [['periode' => 'tahun']],
        ];
    }

    #[DataProvider('periode')]
    public function test_angka_kartu_sama_dengan_total_daftar_tujuan(array $query): void
    {
        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', $query))->assertOk();
        $angka = $r->viewData('statusProyek');
        $this->assertGreaterThan(2, $angka->sum());

        foreach (CastingProject::TAHAP as $tahap => $label) {
            $this->assertSame(1, preg_match('/href="([^"]*tahap='.$tahap.'[^"]*)" class="sa-lihat-semua"/', $r->getContent(), $m), $tahap);
            $url = html_entity_decode($m[1]);
            $this->assertSame($tahap !== 'menunggu_acc', str_contains($url, 'dari='), $tahap);

            $daftar = $this->get($url)->assertOk();
            $this->assertSame($angka[$tahap], $daftar->viewData('projects')->total(), "$tahap $url");
        }
    }

    public function test_chip_periode_tampil_bisa_dihapus_dan_ikut_di_form_cari(): void
    {
        $r = $this->actingAs($this->sa)->get(route('admin.projects.index', ['tahap' => 'mendatang', 'dari' => '2026-06-01', 'sampai' => '2026-06-30']));

        $r->assertOk()->assertSee('Periode: 01 Jun–30 Jun 2026')
            ->assertSee('name="dari" value="2026-06-01"', false)
            ->assertSee('name="sampai" value="2026-06-30"', false)
            ->assertSee(e(route('admin.projects.index', ['tahap' => 'mendatang'])).'"', false);

        $this->get(route('admin.projects.index', ['dari' => 'x', 'sampai' => '2026-06-30']))
            ->assertOk()->assertDontSee('Periode:');
    }
}

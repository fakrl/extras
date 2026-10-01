<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * BI.2: filter jadi panel popover, chip filter aktif bisa dihapus, tanpa tombol Terapkan.
 */
class BiFilterPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    private function form(TestResponse $r): string
    {
        preg_match('/<form[^>]*id="live-form".*?<\/form>/s', $r->getContent(), $m);

        return $m[0] ?? '';
    }

    private function cekPanel(TestResponse $r, int $jumlah): void
    {
        $form = $this->form($r);
        $this->assertStringContainsString('class="fpanel"', $form);
        $this->assertStringNotContainsString('Terapkan', $form);
        $this->assertStringContainsString('<span class="fpanel-n">'.$jumlah.'</span>', $form);
        $this->assertSame($jumlah, substr_count($form, 'class="fchip"'));
    }

    public function test_manajemen_akun_chip_role_dan_tag(): void
    {
        $tag = ExtrasCategory::firstOrCreate(['nama' => 'Berhijab']);
        $url = fn (array $q) => e(route('super-admin.akun.index', $q)).'"';

        $r = $this->actingAs($this->sa)->get(route('super-admin.akun.index', ['q' => 'budi', 'per' => 48, 'role' => 'extras', 'tag' => [$tag->id]]))->assertOk();

        $this->cekPanel($r, 2);
        $r->assertSee('Role: Extras')->assertSee('Tag: #Berhijab')
            ->assertSee('name="role" value="extras" checked', false)
            ->assertSee($url(['q' => 'budi', 'per' => 48, 'tag' => [$tag->id]]), false)
            ->assertSee($url(['q' => 'budi', 'per' => 48, 'role' => 'extras']), false)
            ->assertSee($url(['q' => 'budi', 'per' => 48]).' class="fchips-hapus"', false)
            ->assertSee($url(['per' => 48]).' class="btn btn-sm" target="_self">Reset', false)
            ->assertSee('Menampilkan yang punya <strong>semua</strong> tag terpilih', false);

        $this->get(route('super-admin.akun.index', ['role' => 'admin']))->assertOk()
            ->assertSee('Role: Admin')->assertDontSee('name="tag[]"', false)->assertDontSee('name="grade"', false);
        $this->cekPanel($this->get(route('super-admin.akun.index', ['status' => 'aktif', 'sedang_aktif' => 1])), 2);
    }

    public function test_log_aktivitas_chip_role_dan_tanggal(): void
    {
        $r = $this->actingAs($this->sa)->get(route('super-admin.activity-logs', ['q' => 'x', 'role' => 'client', 'dari' => '2026-09-01']))->assertOk();

        $this->cekPanel($r, 2);
        $r->assertSee('Role: Client')->assertSee('Dari: 01 Sep 2026')
            ->assertSee(e(route('super-admin.activity-logs', ['q' => 'x', 'dari' => '2026-09-01'])).'"', false)
            ->assertSee(e(route('super-admin.activity-logs', ['q' => 'x', 'role' => 'client'])).'"', false)
            ->assertSee(e(route('super-admin.activity-logs', ['q' => 'x'])).'" class="fchips-hapus"', false)
            ->assertSee('href="'.route('super-admin.activity-logs').'" class="btn btn-sm" target="_self">Reset', false);
    }

    public function test_proyek_chip_tahap_dan_tanpa_client(): void
    {
        $r = $this->actingAs($this->sa)->get(route('admin.projects.index', ['per' => 12, 'tahap' => 'berjalan', 'tanpa_client' => 1]))->assertOk();

        $this->cekPanel($r, 2);
        $r->assertSee('Tahap: Berjalan')->assertSee('Client belum diisi')
            ->assertSee(e(route('admin.projects.index', ['per' => 12, 'tanpa_client' => 1])).'"', false)
            ->assertSee(e(route('admin.projects.index', ['per' => 12, 'tahap' => 'berjalan'])).'"', false)
            ->assertSee(e(route('admin.projects.index', ['per' => 12])).'"', false);

        $this->assertStringNotContainsString('fpanel-n', $this->form($this->get(route('admin.projects.index'))));
    }

    /** BQ.2: Reset mengosongkan semua filter + pencarian + page, cuma `per` yang dipertahankan. */
    public function test_reset_mengosongkan_semua_filter_dan_pencarian_di_tiap_halaman(): void
    {
        $tag = ExtrasCategory::firstOrCreate(['nama' => 'Berhijab']);
        $proyek = CastingProject::create(['nama_produksi' => 'Proyek Senja', 'deadline' => now()->addDays(7), 'kuota' => 5, 'status' => 'dibuka', 'client_request_status' => 'disetujui']);
        $kotor = ['q' => 'budi', 'page' => 2, 'per' => 48, 'tag' => [$tag->id], 'grade' => 'A', 'status' => 'aktif', 'favorit' => 1];

        foreach ([
            route('admin.projects.applicants', [$proyek, ...$kotor, 'status' => 'diajukan', 'urut' => 'cocok']),
            route('super-admin.akun.index', [...$kotor, 'role' => 'extras', 'sedang_aktif' => 1]),
            route('super-admin.activity-logs', [...$kotor, 'role' => 'client', 'aktor' => 'x', 'dari' => '2026-09-01']),
            route('admin.projects.index', [...$kotor, 'status' => 'dibuka', 'tahap' => 'berjalan', 'bayar' => 'extras', 'tanpa_client' => 1]),
        ] as $halaman) {
            $html = $this->actingAs($this->sa)->get($halaman)->assertOk()->getContent();
            $this->assertSame(1, preg_match('/href="([^"]*)" class="btn btn-sm" target="_self">Reset/', $html, $m), $halaman);
            $reset = html_entity_decode($m[1]);
            parse_str((string) parse_url($reset, PHP_URL_QUERY), $sisa);
            $this->assertSame(strtok($halaman, '?'), strtok($reset, '?'), $halaman);
            $this->assertSame(['per' => '48'], $sisa, $halaman);
        }
    }

    /** BT: toolbar Lineup dirapikan — Grade/Status/Tag masuk panel filter, chip cuma muncul kalau ada filter aktif. */
    public function test_lineup_chip_filter_hanya_muncul_saat_ada_filter_aktif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create(['nama_produksi' => 'Proyek Chip', 'deadline' => now()->addDays(7), 'kuota' => 5, 'status' => 'dibuka', 'client_request_status' => 'disetujui', 'admin_id' => $admin->id]);

        $kosong = $this->actingAs($admin)->get(route('admin.projects.applicants', $project))->assertOk();
        $this->assertStringNotContainsString('class="fchip"', $kosong->getContent());

        $this->actingAs($admin)->get(route('admin.projects.applicants', [$project, 'grade' => 'A']))
            ->assertOk()->assertSee('class="fchip"', false);

        $this->actingAs($admin)->get(route('admin.projects.applicants', [$project, 'status' => ['lolos']]))
            ->assertOk()->assertSee('class="fchip"', false);
    }
}

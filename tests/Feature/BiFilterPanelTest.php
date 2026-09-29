<?php

namespace Tests\Feature;

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
            ->assertSee($url(['q' => 'budi', 'per' => 48]), false)
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
            ->assertSee(e(route('super-admin.activity-logs', ['q' => 'x'])).'"', false);
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
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chip filter aktif dipindah ke dalam field pencarian (gaya tag-input), bukan lagi di bawahnya.
 * Halaman lama (applicants.blade.php, belum dimigrasi) tetap pakai lokasi chip yang lama.
 */
class CariChipsInlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_chip_role_extras_bersarang_di_dalam_cari_chips(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $html = $this->actingAs($sa)->get(route('super-admin.akun.index', ['role' => 'extras']))->assertOk()->getContent();

        $posWrap = strpos($html, '<div class="cari-chips">');
        $posChip = strpos($html, 'Role: Extras');
        $posInput = strpos($html, 'name="q"');

        $this->assertNotFalse($posWrap);
        $this->assertNotFalse($posChip);
        $this->assertNotFalse($posInput);
        $this->assertTrue($posWrap < $posChip && $posChip < $posInput, 'Chip "Role: Extras" harus ada di dalam wrapper .cari-chips, sebelum input pencarian.');
    }

    public function test_tombol_hapus_semua_muncul_saat_ada_filter_aktif(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->get(route('super-admin.akun.index', ['role' => 'extras']))->assertOk()
            ->assertSee('class="fchips-hapus"', false)
            ->assertSee('aria-label="Hapus semua filter aktif"', false);
    }

    public function test_tombol_filter_tetap_tampilkan_badge_jumlah(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->get(route('super-admin.akun.index', ['role' => 'extras']))->assertOk()
            ->assertSee('<span class="fpanel-n">1</span>', false);
    }

    public function test_tanpa_filter_aktif_tidak_ada_chip_dan_tombol_hapus_semua(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $html = $this->actingAs($sa)->get(route('super-admin.akun.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('class="fchip"', $html);
        $this->assertStringNotContainsString('class="fchips-hapus"', $html);
        $this->assertStringNotContainsString('<span class="fpanel-n">', $html);
    }

    public function test_lineup_lama_belum_dimigrasi_tetap_pakai_lokasi_chip_lama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create(['nama_produksi' => 'Proyek Lama', 'deadline' => now()->addDays(7), 'kuota' => 5, 'status' => 'dibuka', 'client_request_status' => 'disetujui', 'admin_id' => $admin->id]);

        $html = $this->actingAs($admin)->get(route('admin.projects.applicants', [$project, 'grade' => 'A']))->assertOk()->getContent();

        $this->assertStringNotContainsString('class="cari-chips"', $html);
        $posFchipsDiv = strpos($html, '<div class="fchips" data-live-sync="fchips">');
        $posChip = strpos($html, 'class="fchip"');
        $this->assertNotFalse($posFchipsDiv);
        $this->assertNotFalse($posChip);
        $this->assertTrue($posFchipsDiv < $posChip);
    }
}

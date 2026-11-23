<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Services\KeuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rebrand produk "SIM Casting JBTB" -> "SiCasti" (config/brand.php). Legal entity
 * "PT. JBTB Casting Creative Group" di dokumen hukum (kontrak/invoice/payroll/ToS/Privasi) TIDAK berubah.
 */
class RebrandSicastiTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_pakai_nama_brand_dan_tanpa_copy_perusahaan(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(config('brand.name'), $html);
        foreach (['Profil Perusahaan', '5 karyawan tetap', 'Berdiri sejak 2020'] as $copyPerusahaan) {
            $this->assertStringNotContainsString($copyPerusahaan, $html);
        }
    }

    public function test_halaman_tentang_200_dan_sebut_legal_entity(): void
    {
        $this->get(route('tentang'))
            ->assertOk()
            ->assertSee(config('brand.legal_entity'), false)
            ->assertSee('NIB terdaftar OSS');
    }

    public function test_footer_landing_tautkan_ke_tentang(): void
    {
        $this->get('/')->assertOk()->assertSee('href="'.route('tentang').'"', false);
    }

    public function test_pdf_kontrak_dan_invoice_tetap_pakai_nama_legal_lama(): void
    {
        $p = CastingProject::factory()->create();
        $extras = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id, 'nama_asli' => 'Rina', 'nik' => '3201234567890011']);
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'lolos']);

        $kontrakHtml = view('contracts.pdf-template', ['application' => tap($app, fn ($a) => $a->contract()->create())->load('contract', 'extras', 'castingProject')])->render();
        $this->assertStringContainsString('PT. JBTB Casting Creative Group', $kontrakHtml);

        $rincian = app(KeuanganService::class)->rincianInvoice($p);
        $invoiceHtml = view('invoices.pdf-template', ['castingProject' => $p, 'invoice' => $p->invoices()->create(), 'rincian' => $rincian])->render();
        $this->assertStringContainsString('PT. JBTB Casting Creative Group', $invoiceHtml);
    }

    public function test_pdf_payroll_pakai_nama_brand_baru(): void
    {
        $p = CastingProject::factory()->create();
        $staf = User::factory()->create(['role' => 'korlap']);
        $assignment = AdminProjectAssignment::create([
            'casting_project_id' => $p->id,
            'user_id' => $staf->id,
            'assigned_by' => $staf->id,
            'status_log' => 'selesai',
            'completed_at' => now(),
        ]);
        $payroll = StaffPayroll::create([
            'admin_project_assignment_id' => $assignment->id,
            'nominal_pokok' => 500000,
            'status_bayar' => 'belum',
        ]);

        $html = view('payrolls.pdf-template', [
            'assignment' => $assignment->load('user', 'castingProject'),
            'payroll' => $payroll->load('addons'),
        ])->render();

        $this->assertStringContainsString(config('brand.name'), $html);
        $this->assertStringNotContainsString('SIM Casting JBTB', $html);
    }

    public function test_manifest_name_sicasti(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('SiCasti', $data['name']);
    }

    public function test_landing_tidak_ada_hamburger_lama(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('menu-toggle', $html);
    }

    public function test_landing_tidak_ada_dock_bawah(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('<nav class="bottom-dock"', $html);
    }

    public function test_landing_dan_tentang_tidak_ada_nav_sheet_dan_tema_gelap_switch(): void
    {
        foreach (['/', route('tentang')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('<dialog id="nav-sheet"', $html);
            $this->assertStringNotContainsString('Tema gelap', $html);
        }
    }

    public function test_nav_berisi_link_utama_tentang_privasi_sk_hanya_di_footer(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $nav = substr($html, strpos($html, '<nav class="pill-nav"'), strpos($html, '</nav>') + 6 - strpos($html, '<nav class="pill-nav"'));
        foreach (['Lowongan', 'Cara Kerja', 'FAQ'] as $teks) {
            $this->assertStringContainsString($teks, $nav);
        }
        foreach (['Tentang PT JBTB', 'Privasi', 'Syarat dan ketentuan'] as $teks) {
            $this->assertStringNotContainsString($teks, $nav);
        }

        $footer = substr($html, strpos($html, '<footer'));
        foreach (['Tentang PT JBTB', 'Privasi', 'Syarat dan ketentuan'] as $teks) {
            $this->assertStringContainsString($teks, $footer);
        }
    }

    public function test_theme_toggle_berada_di_luar_pill_nav(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $navEnd = strpos($html, '</nav>') + 6;
        $this->assertStringNotContainsString('id="theme-toggle"', substr($html, 0, $navEnd));
        $this->assertStringContainsString('id="theme-toggle"', $html);
    }

    public function test_guest_lihat_masuk_di_dropdown_mobile_dan_pill(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="pill-mobile-masuk"', $html);
        $this->assertStringContainsString('Masuk', $html);
    }

    public function test_user_login_tidak_lihat_masuk_di_dropdown_dan_dropdown_akun_tetap(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('class="pill-mobile-masuk"', $html);
        $this->assertStringContainsString('id="navbar-user-menu"', $html);
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Keluar', $html);
    }

    public function test_dropdown_mobile_tidak_berisi_form_logout(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $start = strpos($html, 'id="pill-mobile-menu"');
        $menu = substr($html, $start, strpos($html, '</div>', $start) - $start);

        $this->assertStringNotContainsString('action="'.route('logout').'"', $menu);
    }

    public function test_landing_render_200_untuk_semua_role(): void
    {
        foreach (['super_admin', 'admin', 'korlap', 'client', 'extras'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/')->assertOk();
        }
    }

    public function test_halaman_tentang_render_untuk_guest_dan_user_login(): void
    {
        $this->get(route('tentang'))->assertOk();

        $user = User::factory()->create(['role' => 'extras']);
        $this->actingAs($user)->get(route('tentang'))->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
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

    public function test_manifest_name_sicasti(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('SiCasti', $data['name']);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC.md Bagian CK: landing ringkas, hapus About/Visi-Misi/Services/footer-cta/modal,
 * tambah Cara Kerja + FAQ, Cast & Portofolio pindah setelah Lowongan.
 */
class CkLandingRingkasTest extends TestCase
{
    use RefreshDatabase;

    public function test_blok_lama_hilang_dan_blok_baru_ada(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['Visi', 'Misi', 'Karyawan', 'Tahun Berdiri', 'InDrive', 'welcome-modal', 'Kenapa<br>JBTB', 'Tentang Kami'] as $hilang) {
            $this->assertStringNotContainsString($hilang, $html);
        }

        foreach (['id="cara-kerja"', 'id="faq"', 'id="lowongan"'] as $ada) {
            $this->assertStringContainsString($ada, $html);
        }
    }

    public function test_faq_berisi_4_butir_sesuai_kode(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $faq = substr($html, (int) strpos($html, 'id="faq"'));

        $this->assertSame(4, substr_count($faq, 'class="lowongan-item"'));
        foreach (['Daftarnya bayar?', 'Kapan honor dibayar?', 'Kalau batal ikut gimana?', 'Data KTP dan rekening aman?'] as $pertanyaan) {
            $this->assertStringContainsString($pertanyaan, $faq);
        }
    }

    public function test_nav_dan_footer_tautan_baru(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('#tentang', $html);
        $this->assertStringNotContainsString('#layanan', $html);
        $this->assertStringContainsString('#cara-kerja', $html);
        $this->assertStringContainsString('#faq', $html);
        $this->assertStringContainsString('PT. JBTB Casting Creative Group', $html);
        $this->assertStringContainsString('NIB terdaftar OSS', $html);
    }
}

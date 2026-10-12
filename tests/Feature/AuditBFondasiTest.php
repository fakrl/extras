<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditBFondasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_tanpa_kontak_tidak_menampilkan_placeholder(): void
    {
        config(['app.kontak' => ['wa' => null, 'email' => null]]);

        $this->get('/')->assertOk()
            ->assertDontSee('Isi nomor WhatsApp')
            ->assertDontSee('Isi email')
            ->assertDontSee('>WhatsApp<', false)
            ->assertSee('id="layanan"', false)
            ->assertSee('<main>', false);
    }

    public function test_landing_dengan_kontak_menampilkan_link(): void
    {
        config(['app.kontak' => ['wa' => '0812 3456 7890', 'email' => 'halo@jbtb.test']]);

        $this->get('/')->assertOk()
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('mailto:halo@jbtb.test', false);
    }

    public function test_halaman_error_bahasa_indonesia_dengan_jalan_kembali(): void
    {
        foreach ([403, 419, 500, 503] as $kode) {
            $html = view("errors.{$kode}")->render();
            $this->assertStringContainsString('lang="id"', $html);
            $this->assertStringNotContainsString('Server Error', $html);
        }

        $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('href="'.route('home').'"', false)->assertSee('Kembali ke Beranda');

        $this->actingAs(User::factory()->create(['role' => 'extras']))
            ->get('/halaman-tidak-ada')->assertNotFound()->assertSee('Kembali ke Dashboard')->assertSee(route('dashboard'), false);
    }

    public function test_privacy_policy_tanggal_tetap_dan_data_baru(): void
    {
        $this->get(route('privacy-policy'))->assertOk()
            ->assertSee('12 Oktober 2026')
            ->assertSee('Nomor WhatsApp')
            ->assertSee('Akun Google')
            ->assertSee('Profil publik')
            ->assertDontSee('video .')
            ->assertSee(route('home'), false);
    }

    public function test_auth_label_terhubung_dan_istilah_kata_sandi(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('for="login-email"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('Kata Sandi')
            ->assertDontSee('tabindex="-1"', false);

        $this->get(route('register'))->assertOk()->assertSee('Minimal 8 karakter.');
    }

    public function test_csp_mengizinkan_gambar_data(): void
    {
        $this->assertStringContainsString("img-src 'self' data:", $this->get('/')->headers->get('Content-Security-Policy'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Restyle Sorot: headline hero dari config('brand.hero_title') dengan kata aksen
 * italic-serif, dan statistik hero cuma tampil kalau datanya nyata (bukan karangan).
 */
class HeroSorotTest extends TestCase
{
    use RefreshDatabase;

    public function test_headline_hero_dari_config_dengan_aksen_italic(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $raw = config('brand.hero_title');
        preg_match('/\*(.+?)\*/', $raw, $m);
        $kataAksen = $m[1];

        $this->assertStringContainsString('<span class="hp-accent-serif">'.$kataAksen.'</span>', $html);
        $this->assertStringNotContainsString('*'.$kataAksen.'*', $html);
    }

    public function test_statistik_hero_tersembunyi_kalau_tidak_ada_data_nyata(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('class="hp-stats"', $html);
        $this->assertStringNotContainsString('class="hp-hero-float"', $html);
    }

    public function test_statistik_hero_tampil_dengan_angka_asli_saat_ada_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        CastingProject::factory()->create([
            'admin_id' => $admin->id,
            'status' => 'dibuka',
            'client_request_status' => 'disetujui',
            'deadline' => now()->addDays(10),
            'kuota' => 5,
        ]);
        ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="hp-stats"', $html);
        $this->assertStringContainsString('<span class="hp-stat-num">1</span><span class="hp-stat-label">Casting dibuka</span>', $html);
        $this->assertStringContainsString('<span class="hp-stat-num">5</span><span class="hp-stat-label">Peran dibuka</span>', $html);
        $this->assertStringContainsString('<span class="hp-stat-num">1</span><span class="hp-stat-label">Talent terdaftar</span>', $html);
        $this->assertStringContainsString('class="hp-hero-float"', $html);
        $this->assertStringContainsString('Sedang dibuka 1 peran', $html);
    }

    /**
     * Guard anti-hotlink: semua <img>/<source> dengan src absolut http(s) harus
     * satu host dengan aplikasi sendiri (asset()/route() aman) - bukan domain luar
     * (situs referensi Sorot, Unsplash, dll).
     */
    public function test_landing_tanpa_gambar_eksternal_hotlink(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $hostSendiri = parse_url(config('app.url'), PHP_URL_HOST);

        preg_match_all('/<(?:img|source)[^>]+src="(https?:\/\/[^"]*)"/i', $html, $m);

        foreach ($m[1] as $src) {
            $this->assertSame($hostSendiri, parse_url($src, PHP_URL_HOST), "Gambar dari domain luar terdeteksi: {$src}");
        }
    }
}

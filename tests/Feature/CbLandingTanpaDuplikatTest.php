<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CbLandingTanpaDuplikatTest extends TestCase
{
    use RefreshDatabase;

    private function cast(int $n, int $dari = 1): void
    {
        for ($i = $dari; $i < $dari + $n; $i++) {
            $user = User::factory()->create(['role' => 'extras', 'username' => "cast_{$i}"]);
            $p = ExtrasProfile::create(['user_id' => $user->id, 'foto_profil_path' => "x/{$i}.jpg"]);
            $p->forceFill(['izin_tampil_publik' => true, 'tampil_di_beranda' => true, 'tampil_di_beranda_at' => now()->addSeconds($i)])->save();
            $p->generateShareToken();
        }
    }

    private function porto(int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            CastingProject::create([
                'admin_id' => User::factory()->create(['role' => 'admin'])->id, 'nama_produksi' => "Film {$i}", 'share_token' => Str::random(32),
                'deadline' => today()->subDays(20), 'kuota' => 5, 'status' => 'ditutup', 'client_request_status' => 'disetujui',
                'tampil_portofolio' => true, 'portofolio_judul' => "Porto {$i}", 'portofolio_tahun' => 2020 + $i,
            ]);
        }
    }

    private function html(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    private function hitung(string $html, string $pola): int
    {
        return substr_count($html, $pola);
    }

    public function test_konstanta_bernama(): void
    {
        $this->assertSame([12, 8, 6, 4], [HomeController::CAST_MAKS, HomeController::PORTO_MAKS, HomeController::CAST_MUAT, HomeController::PORTO_MUAT]);
    }

    public function test_tanpa_data_bagian_tidak_muncul_dan_cast_kurang_dari_4_tersembunyi(): void
    {
        $html = $this->html();
        $this->assertStringNotContainsString('id="cast"', $html);
        $this->assertStringNotContainsString('id="portofolio"', $html);
        $this->cast(3);
        $this->assertStringNotContainsString('id="cast"', $this->html());
    }

    public function test_cast_pas_muat_statis_tanpa_duplikat(): void
    {
        $ada = 0;
        foreach ([4, 6] as $n) {
            $this->cast($n - $ada, $ada + 1);
            $ada = $n;
            $html = $this->html();
            $this->assertSame($n, $this->hitung($html, 'class="cast-card"'));
            $this->assertSame($n, $this->hitung($html, 'class="cast-no"'));
            $this->assertStringNotContainsString('data-dup aria-hidden', $html);
            $this->assertStringNotContainsString('class="marquee-track', $html);
            $this->assertStringNotContainsString('data-marquee style', $html);
            $this->assertStringContainsString('class="baris-statis"', $html);
            foreach (range(1, $n) as $i) {
                $this->assertSame(1, $this->hitung($html, "@cast_{$i}</div>"));
            }
        }
    }

    public function test_cast_lebih_dari_muat_marquee_satu_set_duplikat(): void
    {
        $this->cast(7);
        $html = $this->html();
        $this->assertSame(14, $this->hitung($html, 'class="cast-card"'));
        $this->assertSame(7, $this->hitung($html, 'data-dup aria-hidden="true" tabindex="-1"'));
        $this->assertSame(7, $this->hitung($html, 'class="cast-no"'));
        $this->assertStringContainsString('--marquee-durasi: 35s', $html);
        $this->assertStringNotContainsString('class="baris-statis"', $html);
    }

    public function test_cast_dibatasi_maksimal_12(): void
    {
        $this->cast(20);
        $html = $this->html();
        $this->assertSame(24, $this->hitung($html, 'class="cast-card"'));
        $this->assertSame(12, $this->hitung($html, 'data-dup aria-hidden'));
    }

    public function test_porto_statis_marquee_dan_batas(): void
    {
        $this->porto(2);
        $html = $this->html();
        $this->assertSame(2, $this->hitung($html, 'class="porto-card"'));
        $this->assertStringNotContainsString('data-dup aria-hidden', $html);
        $this->assertStringNotContainsString('class="marquee-track', $html);

        $this->porto(2);
        $this->assertSame(4, $this->hitung($this->html(), 'class="porto-card"'));
        $this->assertStringNotContainsString('data-dup aria-hidden', $this->html());

        $this->porto(1);
        $html = $this->html();
        $this->assertSame(10, $this->hitung($html, 'class="porto-card"'));
        $this->assertSame(5, $this->hitung($html, 'data-dup aria-hidden="true"'));
        $this->assertStringContainsString('--marquee-durasi: 45s', $html);

        $this->porto(10);
        $html = $this->html();
        $this->assertSame(16, $this->hitung($html, 'class="porto-card"'));
        $this->assertSame(8, $this->hitung($html, 'data-dup aria-hidden="true"'));
    }
}

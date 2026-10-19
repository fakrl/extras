<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Carousel geser manual (tanpa duplikat/auto-animasi): satu set kartu asli, dibatasi CAST_MAKS/PORTO_MAKS. */
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
        $this->assertSame([12, 8], [HomeController::CAST_MAKS, HomeController::PORTO_MAKS]);
    }

    public function test_tanpa_data_bagian_tidak_muncul_dan_cast_kurang_dari_4_tersembunyi(): void
    {
        $html = $this->html();
        $this->assertStringNotContainsString('id="cast"', $html);
        $this->assertStringNotContainsString('id="portofolio"', $html);
        $this->cast(3);
        $this->assertStringNotContainsString('id="cast"', $this->html());
    }

    public function test_cast_satu_set_tanpa_duplikat_dan_tanpa_tombol_jeda(): void
    {
        $this->cast(7);
        $html = $this->html();
        $this->assertSame(7, $this->hitung($html, 'class="cast-card"'));
        $this->assertSame(7, $this->hitung($html, 'class="cast-no"'));
        $this->assertStringNotContainsString('data-dup', $html);
        $this->assertStringNotContainsString('marquee-jeda', $html);
        $this->assertStringNotContainsString('--marquee-durasi', $html);
        foreach (range(1, 7) as $i) {
            $this->assertSame(1, $this->hitung($html, "@cast_{$i}</div>"));
        }
    }

    public function test_cast_dibatasi_maksimal_12(): void
    {
        $this->cast(20);
        $html = $this->html();
        $this->assertSame(12, $this->hitung($html, 'class="cast-card"'));
        $this->assertStringNotContainsString('data-dup', $html);
    }

    public function test_porto_satu_set_tanpa_duplikat_dan_batas(): void
    {
        $this->porto(2);
        $html = $this->html();
        $this->assertSame(2, $this->hitung($html, 'class="porto-card"'));
        $this->assertStringNotContainsString('data-dup', $html);

        $this->porto(10);
        $html = $this->html();
        $this->assertSame(8, $this->hitung($html, 'class="porto-card"'));
        $this->assertStringNotContainsString('data-dup', $html);
    }
}

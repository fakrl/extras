<?php

namespace Tests\Feature;

use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** BH.4: filter kategori publik di roster talent beranda, tanpa carousel, fragmen AJAX, tanpa N+1. */
class BhBerandaCastFilterTest extends TestCase
{
    use RefreshDatabase;

    private function extras(string $username, array $tags = []): ExtrasProfile
    {
        $user = User::factory()->create(['role' => 'extras', 'username' => $username, 'status' => 'aktif']);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'foto_profil_path' => 'x/'.$username.'.jpg']);
        $profile->forceFill(['izin_tampil_publik' => true, 'tampil_di_beranda' => true, 'tampil_di_beranda_at' => now()])->save();
        $profile->generateShareToken();
        if ($tags) {
            $profile->categories()->sync(ExtrasCategory::whereIn('nama', $tags)->pluck('id'));
        }

        return $profile;
    }

    private function seedPool(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        $this->extras('cast_dewasa', ['Dewasa']);
        $this->extras('cast_remaja', ['Remaja']);
        $this->extras('cast_hijab', ['Dewasa', 'Berhijab']);
        $this->extras('cast_polos');
    }

    public function test_filter_kategori_publik_valid_hanya_tampilkan_yang_punya_tag(): void
    {
        $this->seedPool();
        $html = $this->get('/?kategori=Berhijab')->assertOk()->getContent();
        $this->assertStringContainsString('@cast_hijab', $html);
        $this->assertStringNotContainsString('@cast_dewasa<', $html);
        $this->assertStringNotContainsString('@cast_remaja<', $html);
        $this->assertStringNotContainsString('@cast_polos<', $html);
    }

    public function test_kategori_privat_diperlakukan_sama_seperti_tanpa_filter(): void
    {
        $this->seedPool();
        $jawa = ExtrasCategory::where('nama', 'Jawa')->first();
        $this->extras('cast_jawa')->categories()->sync([$jawa->id]);

        $htmlPrivat = $this->get('/?kategori=Jawa')->assertOk()->getContent();
        $htmlNgasal = $this->get('/?kategori=garbage-tidak-ada-xyz')->assertOk()->getContent();
        $htmlPolos = $this->get('/')->assertOk()->getContent();

        foreach ([$htmlPrivat, $htmlNgasal, $htmlPolos] as $html) {
            $this->assertStringNotContainsString('Jawa', $html);
            $this->assertStringContainsString('cast-chip is-active', $html);
            $this->assertStringContainsString('>Semua<', $html);
        }
    }

    public function test_chip_dan_kartu_tidak_pernah_render_tag_privat(): void
    {
        $this->seedPool();
        $this->extras('cast_sawo')->categories()->sync(
            ExtrasCategory::whereIn('nama', ['Sawo matang', 'Dewasa'])->pluck('id')
        );

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('Sawo matang', $html);
        $this->assertStringNotContainsString('data-kategori="Sawo matang"', $html);
    }

    public function test_fragmen_ajax_cuma_markup_grid_chip_dan_no_store(): void
    {
        $this->seedPool();
        $res = $this->get('/', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $res->assertHeader('Vary', 'X-Requested-With');
        $html = $res->getContent();
        $this->assertStringContainsString('id="cast-fragment"', $html);
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('</body>', $html);

        $html2 = $this->get('/?fragmen=cast')->assertOk()->getContent();
        $this->assertStringContainsString('id="cast-fragment"', $html2);
        $this->assertStringNotContainsString('<html', $html2);
    }

    public function test_filter_kosong_tampilkan_pesan_dan_tombol_tampilkan_semua(): void
    {
        $this->seedPool();
        $html = $this->get('/?kategori=Remaja')->assertOk()->getContent();
        // cast_remaja satu-satunya pemilik tag Remaja -> bukan kosong, cuma pastikan markup normal
        $this->assertStringContainsString('@cast_remaja', $html);

        $this->extras('cast_naik_motor', ['Naik motor']);
        $html = $this->get('/?kategori=Berenang')->assertOk()->getContent();
        $this->assertStringContainsString('Belum ada talent untuk kategori ini.', $html);
        $this->assertStringContainsString('Tampilkan semua', $html);
    }

    public function test_tanpa_n_plus_1_pada_chip_dan_kartu(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        foreach (range(1, 10) as $i) {
            $this->extras("cast_n{$i}", ['Dewasa', 'Berhijab']);
        }
        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $q10 = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        foreach (range(11, 25) as $i) {
            $this->extras("cast_n{$i}", ['Remaja', 'Atlet']);
        }
        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $q25 = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(20, $q10, "Query utk 10 talent: {$q10}");
        $this->assertLessThanOrEqual($q10 + 3, $q25, "Query tidak boleh scaling linear: 10 talent={$q10}, 25 talent={$q25}");
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rebrand warna teal (SiCasti): pastikan hex hijau lama sudah hilang dari
 * <style> inline halaman utama, dan semua aset brand yang dirujuk benar ada.
 */
class BrandRebrandTest extends TestCase
{
    use RefreshDatabase;

    /** Fingerprint unik partials/theme-style.blade.php (WIP, di luar scope rebrand ini). */
    private const THEME_STYLE_FINGERPRINT = 'M3 tokens';

    private function styleBlocksBukanThemeStyle(string $html): string
    {
        preg_match_all('/<style\b[^>]*>(.*?)<\/style>/s', $html, $m);

        return collect($m[1] ?? [])
            ->reject(fn ($block) => str_contains($block, self::THEME_STYLE_FINGERPRINT))
            ->implode("\n");
    }

    public function test_landing_login_dan_dashboard_tidak_pakai_hex_hijau_lama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $halaman = [
            'landing' => $this->get(route('home'))->assertOk()->getContent(),
            'login' => $this->get(route('login'))->assertOk()->getContent(),
            'admin dashboard' => $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent(),
        ];

        foreach ($halaman as $nama => $html) {
            $style = $this->styleBlocksBukanThemeStyle($html);

            $this->assertStringNotContainsStringIgnoringCase('#15803D', $style, "{$nama}: masih ada hex hijau lama #15803D di <style>");
            $this->assertStringNotContainsStringIgnoringCase('#0b1a12', $style, "{$nama}: masih ada hex hijau lama #0b1a12 di <style>");
        }
    }

    public function test_semua_aset_brand_yang_dirujuk_benar_ada_di_filesystem(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $paths = collect($manifest['icons'])->pluck('src')->map(fn ($src) => ltrim($src, '/'))->all();

        $layouts = [
            'views/layouts/app.blade.php',
            'views/layouts/auth.blade.php',
            'views/welcome.blade.php',
            'views/tentang.blade.php',
        ];

        foreach ($layouts as $layout) {
            $isi = file_get_contents(resource_path($layout));
            preg_match_all('/images\/brand\/[a-zA-Z0-9\-\.]+/', $isi, $m);
            $paths = array_merge($paths, $m[0] ?? []);
        }

        $paths = array_unique($paths);
        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $this->assertFileExists(public_path($path), "Aset brand hilang: {$path}");
        }
    }
}

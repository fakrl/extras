<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_reachable_dengan_content_type_benar(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');
    }

    public function test_manifest_adalah_json_valid_dan_punya_field_wajib(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($data);
        $this->assertSame('JBTB Casting', $data['name']);
        $this->assertSame('standalone', $data['display']);
        $this->assertSame('/', $data['start_url']);
        $this->assertNotEmpty($data['icons']);
        $this->assertTrue(collect($data['icons'])->contains(fn ($icon) => ($icon['purpose'] ?? null) === 'maskable'));
    }

    public function test_manifest_terpasang_di_layout_app_auth_dan_welcome(): void
    {
        $this->assertStringContainsString('<link rel="manifest"', file_get_contents(resource_path('views/layouts/app.blade.php')));
        $this->assertStringContainsString('<link rel="manifest"', file_get_contents(resource_path('views/layouts/auth.blade.php')));
        $this->assertStringContainsString('<link rel="manifest"', file_get_contents(resource_path('views/welcome.blade.php')));
    }

    public function test_manifest_terlihat_pada_halaman_login_dan_beranda(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('<link rel="manifest"', false);
        $this->get(route('home'))->assertOk()->assertSee('<link rel="manifest"', false);
    }

    public function test_sw_tidak_menaruh_rute_sensitif_di_daftar_precache(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        preg_match('/PRECACHE_URLS\s*=\s*\[(.*?)\];/s', $sw, $m);
        $this->assertNotEmpty($m, 'PRECACHE_URLS tidak ditemukan di sw.js');
        $precache = $m[1];

        foreach (['/media/', '/pembayaran', '/kontrak', '/invoice'] as $rute) {
            $this->assertStringNotContainsString($rute, $precache);
        }
    }

    public function test_sw_menandai_rute_sensitif_sebagai_never_cache(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        preg_match('/NEVER_CACHE_PREFIXES\s*=\s*\[(.*?)\];/s', $sw, $m);
        $this->assertNotEmpty($m, 'NEVER_CACHE_PREFIXES tidak ditemukan di sw.js');
        $neverCache = $m[1];

        foreach (['/media', '/pembayaran', '/kontrak', '/invoice'] as $rute) {
            $this->assertStringContainsString($rute, $neverCache);
        }
    }
}

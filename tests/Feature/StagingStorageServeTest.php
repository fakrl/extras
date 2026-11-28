<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * DB.2 fix: disk 'local' (tanpa 'url') dan disk 'public' (url=APP_URL.'/storage')
 * sama-sama default ke URI '/storage' kalau serve=>true dua-duanya - bentrok fatal
 * saat boot (lihat docs/STAGING-DEPLOY.md §1.2). Disk 'local' sekarang dimatikan
 * serve-nya persis saat STAGING_NO_SYMLINK=true (satu-satunya saat disk 'public'
 * ikut serve=>true).
 */
class StagingStorageServeTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('STAGING_NO_SYMLINK');
        unset($_ENV['STAGING_NO_SYMLINK']);

        parent::tearDown();
    }

    public function test_default_tanpa_env_disk_local_tetap_serve_sendirian(): void
    {
        putenv('STAGING_NO_SYMLINK');
        unset($_ENV['STAGING_NO_SYMLINK']);

        $config = require base_path('config/filesystems.php');

        $this->assertTrue($config['disks']['local']['serve'], 'local harus tetap serve=>true di produksi/dev (perilaku lama).');
        $this->assertFalse($config['disks']['public']['serve']);
    }

    public function test_staging_no_symlink_true_disk_public_serve_disk_local_mati(): void
    {
        putenv('STAGING_NO_SYMLINK=true');
        $_ENV['STAGING_NO_SYMLINK'] = 'true';

        $config = require base_path('config/filesystems.php');

        $this->assertTrue($config['disks']['public']['serve']);
        $this->assertFalse($config['disks']['local']['serve'], 'local harus mati pas public ikut serve, biar tidak bentrok URI /storage.');
    }

    public function test_route_storage_tidak_bentrok_saat_staging_no_symlink_true(): void
    {
        putenv('STAGING_NO_SYMLINK=true');
        $_ENV['STAGING_NO_SYMLINK'] = 'true';

        $this->refreshApplication();

        $uris = collect($this->app['router']->getRoutes())
            ->map(fn ($route) => $route->methods()[0].' '.$route->uri())
            ->all();

        $this->assertSame(count($uris), count(array_unique($uris)), 'Tidak boleh ada URI+method ganda (ini yang bikin boot crash sebelumnya).');
        $this->assertTrue($this->app['router']->has('storage.public'));
        $this->assertFalse($this->app['router']->has('storage.local'));
    }

    public function test_file_disk_public_terlayani_disk_local_tidak_lewat_storage(): void
    {
        putenv('STAGING_NO_SYMLINK=true');
        $_ENV['STAGING_NO_SYMLINK'] = 'true';

        $this->refreshApplication();

        \Illuminate\Support\Facades\Storage::disk('public')->put('poster-test.txt', 'PUBLIK_TEST');
        \Illuminate\Support\Facades\Storage::disk('local')->put('rahasia-test.txt', 'RAHASIA_PRIVATE_TEST');

        $response = $this->get('/storage/poster-test.txt')->assertOk();
        $this->assertStringContainsString('PUBLIK_TEST', $response->streamedContent());

        // Rute 'storage.local' sengaja tidak terdaftar (lihat test di atas), jadi
        // request ke sini jatuh ke 404/fallback - yang penting BUKAN 200 dan BUKAN
        // isi file private (bukan soal kode status presisi mana).
        $forbidden = $this->get('/storage/rahasia-test.txt');
        $this->assertNotEquals(200, $forbidden->status());
        $forbidden->assertDontSee('RAHASIA_PRIVATE_TEST');

        \Illuminate\Support\Facades\Storage::disk('public')->delete('poster-test.txt');
        \Illuminate\Support\Facades\Storage::disk('local')->delete('rahasia-test.txt');
    }
}

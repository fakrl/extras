<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAkunCatatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_akun_tampilkan_catatan_dan_tetap_sembunyikan_akun_sendiri(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin', 'name' => 'Super Admin Saya']);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Lain']);

        $html = $this->actingAs($sa)->get(route('super-admin.akun.index'))
            ->assertOk()
            ->assertSee('Akun Anda tidak ditampilkan di tabel ini.')
            ->assertSee('Admin Lain')
            ->getContent();

        $awal = strpos($html, 'akun-row');
        $akhir = strpos($html, '</form>', strrpos($html, 'akun-row'));
        $this->assertStringNotContainsString('Super Admin Saya', substr($html, $awal, $akhir - $awal));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Item 11: cek setiap aksi simpan bagian profil Extras balik dengan flash
 * 'status' (banner sukses), bukan cuma redirect polos. Hasil investigasi:
 * semua bagian (data_diri/kontak/tentang/pengalaman/tautan/privasi) lewat
 * satu method update() yang SELALU redirect dengan with('status', ...) di
 * setiap cabangnya - tidak ketemu yang hilang. Tes ini mengunci itu supaya
 * ke depan tidak diam-diam regresi.
 */
class ProfilEditFlashStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_setiap_bagian_simpan_profil_flash_status(): void
    {
        $user = User::factory()->create(['role' => 'extras']);

        foreach (['data_diri', 'kontak', 'tentang', 'pengalaman', 'tautan', 'privasi'] as $bagian) {
            $payload = ['bagian' => $bagian];
            if ($bagian === 'data_diri') {
                $payload += ['nama_asli' => 'Budi Santoso', 'username' => 'budi_'.$user->id];
            }

            $this->actingAs($user->fresh())->put(route('extras.profile.update'), $payload)
                ->assertSessionHas('status');
        }
    }
}

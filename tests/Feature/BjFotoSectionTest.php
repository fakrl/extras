<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC BJ.2: foto utama + galeri satu section, kotak "+" ke slot kosong pertama.
 */
class BjFotoSectionTest extends TestCase
{
    use RefreshDatabase;

    private function halaman(array $slot)
    {
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::factory()->create(['user_id' => $user->id]);
        foreach ($slot as $s) {
            $profile->photos()->create(['urutan' => $s, 'path' => "x/{$s}.jpg"]);
        }

        return $this->actingAs($user)->get(route('extras.profile.edit'))->assertOk();
    }

    public function test_galeri_kosong_alert_dan_tambah_ke_slot_1(): void
    {
        $this->halaman([])
            ->assertSee('<div class="profile-section-title">Foto</div>', false)
            ->assertSee('<span class="foto-wajib">Wajib</span>', false)
            ->assertSee('class="foto-hint is-kosong"', false)
            ->assertSee('Profil dengan 3+ foto (close-up, setengah badan, seluruh badan) lebih sering dipilih Client.')
            ->assertSee('<label for="upload-slot-1" class="foto-box foto-tambah" id="foto-tambah"', false)
            ->assertDontSee('Gallery')
            ->assertSee('Video Perkenalan');
    }

    public function test_dua_foto_tambah_ke_slot_kosong_pertama(): void
    {
        $this->halaman([1, 3])
            ->assertSee('class="foto-hint"', false)
            ->assertDontSee('class="foto-hint is-kosong"', false)
            ->assertSee('<label for="upload-slot-2" class="foto-box foto-tambah"', false)
            ->assertSee('Hapus foto galeri 1')
            ->assertSee('Hapus foto galeri 3');
    }

    public function test_galeri_penuh_tanpa_kotak_tambah(): void
    {
        $this->halaman([1, 2, 3, 4])->assertDontSee('id="foto-tambah"', false)->assertSee('Hapus foto galeri 4');
    }
}

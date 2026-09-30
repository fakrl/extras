<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC BJ.3: pengalaman jadi daftar "+", teks lama jadi entri pertama.
 */
class BjPengalamanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'extras', 'username' => 'rina']);
        ExtrasProfile::create(['user_id' => $this->user->id]);
    }

    private function simpan(array $judul, array $ket = [], array $tahun = [])
    {
        return $this->actingAs($this->user)->put('/extras/profil', [
            'nama_asli' => 'Rina', 'username' => 'rina',
            'pengalaman_judul' => $judul, 'pengalaman_keterangan' => $ket, 'pengalaman_tahun' => $tahun,
        ]);
    }

    public function test_simpan_tiga_pengalaman_urut_tahun_terbaru(): void
    {
        $this->simpan(['Figuran iklan Ramadan', 'Warga di FTV', '', 'Teater kampus'], ['Stasiun kereta, pagi', '', '', ''], [2023, 2025, '', ''])
            ->assertRedirect()->assertSessionHasNoErrors();

        $profile = $this->user->extrasProfile->fresh();
        $this->assertCount(3, $profile->riwayat_pengalaman);
        $this->assertEquals(['judul' => 'Figuran iklan Ramadan', 'keterangan' => 'Stasiun kereta, pagi', 'tahun' => 2023], $profile->riwayat_pengalaman[0]);
        $this->assertSame(['Warga di FTV', 'Figuran iklan Ramadan', 'Teater kampus'], array_column($profile->pengalamanUrut(), 'judul'));

        $this->actingAs($this->user)->get(route('extras.profile.show'))->assertOk()
            ->assertSeeInOrder(['Warga di FTV', 'Figuran iklan Ramadan', 'Stasiun kereta, pagi', 'Teater kampus']);
        $this->actingAs($this->user)->get(route('extras.profile.edit'))->assertOk()
            ->assertSeeInOrder(['Tentang Kamu', 'Pengalaman', 'Kemampuan (naik motor, menari, dll) tambahkan sebagai tag di atas.', 'Tautan Tambahan', 'Punya portofolio/showreel? Taruh link-nya di sini.'])
            ->assertSee('value="Warga di FTV"', false);

        $profile->generateShareToken();
        $this->get(route('public.extras.profile', $profile->fresh()->share_token))->assertSee('Warga di FTV');

        $this->actingAs($this->user)->put('/extras/profil', ['nama_asli' => 'Rina', 'username' => 'rina'])->assertRedirect();
        $this->assertCount(3, $profile->fresh()->riwayat_pengalaman);
    }

    public function test_judul_wajib_per_baris_dan_maks_20(): void
    {
        $this->simpan([''], ['Tanpa judul'], [2024])->assertSessionHasErrors('pengalaman_judul.0');
        $this->simpan(array_fill(0, 21, 'Figuran'))->assertSessionHasErrors('pengalaman_judul');
        $this->assertNull($this->user->extrasProfile->fresh()->riwayat_pengalaman);

        $this->actingAs($this->user)->from(route('extras.profile.edit'))->followingRedirects()
            ->put('/extras/profil', ['nama_asli' => 'Rina', 'username' => 'rina', 'pengalaman_judul' => ['Baris satu', ''], 'pengalaman_tahun' => ['1800', '']])
            ->assertSee('value="Baris satu"', false);
    }
}

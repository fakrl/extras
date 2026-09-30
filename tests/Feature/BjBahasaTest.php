<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permintaan Fakrul (BJ): bahasa jadi daftar "+", kolom tetap string dipisah koma.
 */
class BjBahasaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'extras', 'username' => 'rina']);
        ExtrasProfile::create(['user_id' => $this->user->id]);
    }

    private function simpan($bahasa)
    {
        return $this->actingAs($this->user)->put('/extras/profil', ['nama_asli' => 'Rina', 'username' => 'rina', 'bahasa' => $bahasa]);
    }

    public function test_simpan_daftar_bahasa_dedupe_dan_tampil_chip(): void
    {
        $this->simpan([' Indonesia ', 'Inggris', '', 'indonesia', 'Arab'])->assertSessionHasNoErrors();
        $profile = $this->user->extrasProfile->fresh();
        $this->assertSame('Indonesia, Inggris, Arab', $profile->bahasa);

        $this->actingAs($this->user)->get(route('extras.profile.show'))
            ->assertSee('<span class="pe-chip">Indonesia</span>', false)
            ->assertSee('<span class="pe-chip">Inggris</span>', false)
            ->assertSee('<span class="pe-chip">Arab</span>', false);
        $this->actingAs($this->user)->get(route('extras.profile.edit'))
            ->assertSee('id="bahasa_2" value="Arab"', false)
            ->assertSee('+ Tambah bahasa');
    }

    public function test_maks_10_dan_string_lama_tetap_diterima(): void
    {
        $this->simpan(array_map(fn ($i) => "Bahasa {$i}", range(1, 11)))->assertSessionHasErrors('bahasa');
        $this->simpan([str_repeat('a', 41)])->assertSessionHasErrors('bahasa.0');

        $this->simpan('Indonesia, Jawa')->assertSessionHasNoErrors();
        $this->assertSame('Indonesia, Jawa', $this->user->extrasProfile->fresh()->bahasa);

        $this->simpan([''])->assertSessionHasNoErrors();
        $this->assertNull($this->user->extrasProfile->fresh()->bahasa);
    }
}

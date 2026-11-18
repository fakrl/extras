<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class NamaAsliRegexTest extends TestCase
{
    use RefreshDatabase;

    private function simpan(string $namaAsli): TestResponse
    {
        $user = User::factory()->create(['role' => 'extras']);

        return $this->actingAs($user)->put(route('extras.profile.update'), [
            'bagian' => 'data_diri', 'nama_asli' => $namaAsli, 'username' => 'user_'.$user->id,
        ]);
    }

    public function test_tag_script_ditolak(): void
    {
        $this->simpan('<script>alert(1)</script>')->assertSessionHasErrors('nama_asli');
    }

    public function test_emoji_ditolak(): void
    {
        $this->simpan('Budi 😀')->assertSessionHasErrors('nama_asli');
    }

    public function test_angka_ditolak(): void
    {
        $this->simpan('Budi123')->assertSessionHasErrors('nama_asli');
    }

    public function test_nama_wajar_dengan_strip_dan_apostrof_diterima(): void
    {
        $this->simpan("Siti Nur'aini-Putri")->assertSessionDoesntHaveErrors('nama_asli');
    }

    public function test_nama_wajar_biasa_diterima(): void
    {
        $this->simpan('Budi Santoso')->assertSessionDoesntHaveErrors('nama_asli');
    }
}

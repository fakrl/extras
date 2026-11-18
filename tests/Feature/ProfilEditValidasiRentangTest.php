<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProfilEditValidasiRentangTest extends TestCase
{
    use RefreshDatabase;

    private function simpanDataDiri(array $override = []): TestResponse
    {
        $user = User::factory()->create(['role' => 'extras']);

        return $this->actingAs($user)->put(route('extras.profile.update'), array_merge([
            'bagian' => 'data_diri', 'nama_asli' => 'Budi Santoso', 'username' => 'budi_santoso_'.$user->id,
            'usia' => 25, 'tinggi_badan' => 170, 'berat_badan' => 60,
        ], $override));
    }

    public function test_tinggi_badan_di_atas_210_ditolak(): void
    {
        $this->simpanDataDiri(['tinggi_badan' => 300])->assertSessionHasErrors('tinggi_badan');
    }

    public function test_usia_di_bawah_17_ditolak(): void
    {
        $this->simpanDataDiri(['usia' => 10])->assertSessionHasErrors('usia');
    }

    public function test_usia_di_atas_70_ditolak(): void
    {
        $this->simpanDataDiri(['usia' => 90])->assertSessionHasErrors('usia');
    }

    public function test_berat_badan_di_luar_rentang_ditolak(): void
    {
        $this->simpanDataDiri(['berat_badan' => 10])->assertSessionHasErrors('berat_badan');
        $this->simpanDataDiri(['berat_badan' => 200])->assertSessionHasErrors('berat_badan');
    }

    public function test_rentang_wajar_diterima(): void
    {
        $this->simpanDataDiri()->assertSessionDoesntHaveErrors(['usia', 'tinggi_badan', 'berat_badan']);
    }

    public function test_form_profil_edit_punya_novalidate(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $this->actingAs($user)->get(route('extras.profile.edit'))->assertSee('novalidate', false);
    }
}

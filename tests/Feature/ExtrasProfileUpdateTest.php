<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExtrasProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('formatNomorWaProvider')]
    public function test_nomor_wa_disimpan_ternormalisasi_ke_users_bukan_extras_profiles(string $input): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'kontak',
            'nomor_wa' => $input,
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('628123456789', $user->nomor_wa);
        $this->assertArrayNotHasKey('nomor_wa', $user->extrasProfile->getAttributes());
    }

    public static function formatNomorWaProvider(): array
    {
        return [
            '08xx' => ['08123456789'],
            '+62xx' => ['+628123456789'],
            '62xx' => ['628123456789'],
        ];
    }

    public function test_update_tanpa_nomor_wa_tidak_error_dan_tetap_null(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'kontak',
        ])->assertRedirect();

        $this->assertNull($user->refresh()->nomor_wa);
    }

    // CL.5.1: menyimpan bagian Kontak tidak ikut mengubah bagian Tautan (rate_card), dan sebaliknya.
    public function test_simpan_kontak_tidak_mengubah_tautan_dan_sebaliknya(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/extras/profil', ['bagian' => 'tautan', 'rate_card' => 300000])->assertRedirect();
        $this->actingAs($user)->put('/extras/profil', ['bagian' => 'kontak', 'nomor_wa' => '081234567890'])->assertRedirect();

        $profile->refresh();
        $this->assertEquals(300000, $profile->rate_card);
        $this->assertSame('6281234567890', $user->refresh()->nomor_wa);
    }

    // CL.1.5: akun baru/profil belum lengkap, bagian Data Diri auto-terbuka.
    public function test_akun_baru_bagian_data_diri_otomatis_terbuka(): void
    {
        $user = User::factory()->create(['role' => 'extras']);

        $html = $this->actingAs($user)->get(route('extras.profile.edit'))->getContent();

        $this->assertMatchesRegularExpression('/<details id="bagian-data_diri"[^>]*\bopen\b[^>]*>/', $html);
    }

    // CL.5.2: profil lengkap -> "Profil siap melamar", tidak ada bagian otomatis terbuka, ada tombol "Lihat seperti Client".
    public function test_profil_lengkap_siap_melamar_tanpa_bagian_otomatis_terbuka(): void
    {
        $profile = ExtrasProfile::factory()->create(['foto_profil_path' => 'extras/foto/dummy.jpg']);
        $user = $profile->user;

        $html = $this->actingAs($user)->get(route('extras.profile.edit'))->getContent();

        $this->assertStringContainsString('Profil siap melamar', $html);
        $this->assertDoesNotMatchRegularExpression('/<details id="bagian-data_diri"[^>]*\bopen\b[^>]*>/', $html);
        $this->assertStringContainsString('Lihat seperti Client', $html);
        $this->assertStringContainsString(route('extras.profile.show'), $html);
    }

    // CL.5.1: gagal validasi di satu bagian (tautan) tetap kembali ke halaman yang sama, dan bagian itu teridentifikasi lewat old('bagian').
    public function test_bagian_tautan_gagal_validasi_tetap_teridentifikasi_terbuka(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id]);

        $this->actingAs($user)->from(route('extras.profile.edit'))->put('/extras/profil', [
            'bagian' => 'tautan',
            'tautan_label' => ['Instagram'],
            'tautan_url' => ['bukan-url-valid'],
        ])->assertSessionHasErrors('tautan_url.0')->assertRedirect(route('extras.profile.edit'));

        $html = $this->actingAs($user)->get(route('extras.profile.edit'))->getContent();

        $this->assertMatchesRegularExpression('/<details id="bagian-tautan"[^>]*\bopen\b[^>]*>/', $html);
    }

    public function test_galat_tautan_url_tampil_bahasa_indonesia(): void
    {
        app()->setLocale('id');

        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->from(route('extras.profile.edit'))->put('/extras/profil', [
            'bagian' => 'tautan',
            'tautan_label' => ['Instagram'],
            'tautan_url' => ['bukan-url-valid'],
        ]);

        $response->assertSessionHasErrors(['tautan_url.0' => 'URL tautan harus berupa URL yang valid.']);
    }
}

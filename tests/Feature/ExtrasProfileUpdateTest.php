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
        ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Lama']);

        $this->actingAs($user)->put('/extras/profil', [
            'alias' => 'Alias Lama',
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
        ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Lama']);

        $this->actingAs($user)->put('/extras/profil', [
            'alias' => 'Alias Lama',
        ])->assertRedirect();

        $this->assertNull($user->refresh()->nomor_wa);
    }

    public function test_field_profile_lain_tetap_tersimpan_bareng_nomor_wa(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Lama']);

        $this->actingAs($user)->put('/extras/profil', [
            'alias' => 'Alias Baru',
            'rate_card' => 300000,
            'nomor_wa' => '081234567890',
        ])->assertRedirect();

        $profile->refresh();
        $this->assertSame('Alias Baru', $profile->alias);
        $this->assertEquals(300000, $profile->rate_card);
        $this->assertSame('6281234567890', $user->refresh()->nomor_wa);
    }
}

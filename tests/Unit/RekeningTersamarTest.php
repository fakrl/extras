<?php

namespace Tests\Unit;

use App\Models\ExtrasProfile;
use Tests\TestCase;

/**
 * rekeningTersamar() harus ambil 4 digit TERAKHIR dari nomor rekening,
 * bukan 4 karakter terakhir dari teks bebas.
 */
class RekeningTersamarTest extends TestCase
{
    public function test_ambil_4_digit_terakhir_dari_teks_bebas(): void
    {
        $profile = new ExtrasProfile(['rekening' => 'BCA 1234500011 a.n. Aisyah Fauziah']);

        $this->assertSame('•••• 0011', $profile->rekeningTersamar());
    }

    public function test_tanpa_digit_jatuh_ke_bullet_penuh(): void
    {
        $profile = new ExtrasProfile(['rekening' => 'no rek']);

        $this->assertSame('•••• ••••', $profile->rekeningTersamar());
    }

    public function test_digit_kurang_dari_4_jatuh_ke_bullet_penuh(): void
    {
        $profile = new ExtrasProfile(['rekening' => 'ab12']);

        $this->assertSame('•••• ••••', $profile->rekeningTersamar());
    }

    public function test_null_rekening_null(): void
    {
        $profile = new ExtrasProfile(['rekening' => null]);

        $this->assertNull($profile->rekeningTersamar());
    }
}

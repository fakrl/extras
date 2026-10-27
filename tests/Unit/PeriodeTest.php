<?php

namespace Tests\Unit;

use App\Support\Periode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * CR.1.1/CR.5.1: tiap preset Periode::dari() baru (Hari ini, 7, 30, Bulan ini,
 * Bulan lalu, Tahun ini, Kustom) menghasilkan rentang tanggal yang persis.
 */
class PeriodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_preset_hari_ini(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => 'hari_ini']), '30');

        $this->assertSame('2026-06-15', $dari->toDateString());
        $this->assertSame('2026-06-15', $sampai->toDateString());
        $this->assertSame('hari_ini', $preset);
    }

    public function test_preset_7_hari_terakhir_inklusif(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => '7']), '30');

        $this->assertSame('2026-06-09', $dari->toDateString());
        $this->assertSame('2026-06-15', $sampai->toDateString());
        $this->assertSame('7', $preset);
    }

    public function test_preset_30_hari_terakhir(): void
    {
        [$dari, $sampai] = Periode::dari(new Request(['periode' => '30']), 'hari_ini');

        $this->assertSame('2026-05-17', $dari->toDateString());
        $this->assertSame('2026-06-15', $sampai->toDateString());
    }

    public function test_preset_bulan_ini(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => 'bulan_ini']), '30');

        $this->assertSame('2026-06-01', $dari->toDateString());
        $this->assertSame('2026-06-30', $sampai->toDateString());
        $this->assertSame('bulan_ini', $preset);
    }

    public function test_preset_bulan_lalu(): void
    {
        [$dari, $sampai] = Periode::dari(new Request(['periode' => 'bulan_lalu']), '30');

        $this->assertSame('2026-05-01', $dari->toDateString());
        $this->assertSame('2026-05-31', $sampai->toDateString());
    }

    public function test_preset_tahun_ini(): void
    {
        [$dari, $sampai] = Periode::dari(new Request(['periode' => 'tahun_ini']), '30');

        $this->assertSame('2026-01-01', $dari->toDateString());
        $this->assertSame('2026-12-31', $sampai->toDateString());
    }

    public function test_preset_lama_yang_dihapus_jatuh_ke_default(): void
    {
        foreach (['14', '60', '90', '365', '+7', '+30'] as $presetLama) {
            [, , $preset] = Periode::dari(new Request(['periode' => $presetLama]), '30');
            $this->assertSame('30', $preset, "preset lama '$presetLama' seharusnya jatuh ke default");
        }
    }

    public function test_custom_dari_sampai_menang_atas_preset(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => '7', 'dari' => '2026-01-01', 'sampai' => '2026-01-10']), '30');

        $this->assertSame('2026-01-01', $dari->toDateString());
        $this->assertSame('2026-01-10', $sampai->toDateString());
        $this->assertNull($preset);
    }

    public function test_custom_terbalik_dinormalisasi(): void
    {
        [$dari, $sampai] = Periode::dari(new Request(['dari' => '2026-01-10', 'sampai' => '2026-01-01']), '30');

        $this->assertSame('2026-01-01', $dari->toDateString());
        $this->assertSame('2026-01-10', $sampai->toDateString());
    }

    public function test_input_rusak_jatuh_ke_default(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => 'ngaco', 'dari' => 'bukan-tanggal']), '30');

        $this->assertSame('2026-05-17', $dari->toDateString());
        $this->assertSame('2026-06-15', $sampai->toDateString());
        $this->assertSame('30', $preset);
    }

    public function test_tanpa_input_jatuh_ke_default(): void
    {
        [, , $preset] = Periode::dari(new Request, 'bulan_ini');

        $this->assertSame('bulan_ini', $preset);
    }

    public function test_label_preset_pakai_nama_preset(): void
    {
        $this->assertSame('30 hari terakhir', Periode::label(today()->subDays(29), today(), '30'));
        $this->assertSame('Bulan ini', Periode::label(today()->startOfMonth(), today()->endOfMonth(), 'bulan_ini'));
    }
}

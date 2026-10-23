<?php

namespace Tests\Unit;

use App\Support\Periode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * CQ.5.1: tiap preset Periode::dari() menghasilkan rentang tanggal yang persis.
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

    public function test_preset_7_hari_mundur_inklusif(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => '7']), '30');

        $this->assertSame('2026-06-09', $dari->toDateString());
        $this->assertSame('2026-06-15', $sampai->toDateString());
        $this->assertSame('7', $preset);
    }

    public function test_preset_365_hari_mundur(): void
    {
        [$dari, $sampai] = Periode::dari(new Request(['periode' => '365']), '30');

        $this->assertSame('2025-06-16', $dari->toDateString());
        $this->assertSame('2026-06-15', $sampai->toDateString());
    }

    public function test_preset_maju_7_hari(): void
    {
        [$dari, $sampai, $preset] = Periode::dari(new Request(['periode' => '+7']), '30');

        $this->assertSame('2026-06-15', $dari->toDateString());
        $this->assertSame('2026-06-21', $sampai->toDateString());
        $this->assertSame('+7', $preset);
    }

    public function test_preset_maju_30_hari(): void
    {
        [$dari, $sampai] = Periode::dari(new Request(['periode' => '+30']), '7');

        $this->assertSame('2026-06-15', $dari->toDateString());
        $this->assertSame('2026-07-14', $sampai->toDateString());
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
        [, , $preset] = Periode::dari(new Request, '+30');

        $this->assertSame('+30', $preset);
    }
}

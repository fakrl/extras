<?php

namespace Tests\Unit;

use App\Support\Timeline;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * CR.1.2/CR.5.1: skala hari/minggu/bulan + geser ‹ › + "kembali ke hari ini".
 */
class TimelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Senin.
        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_skala_hari_satu_tanggal(): void
    {
        $t = Timeline::geser(today(), 'hari', 0);
        $this->assertSame('2026-06-15', $t);
    }

    public function test_skala_minggu_senin_sampai_minggu(): void
    {
        $dari = today()->startOfWeek();
        $sampai = today()->endOfWeek();

        $this->assertSame('2026-06-15', $dari->toDateString());
        $this->assertSame('2026-06-21', $sampai->toDateString());
    }

    public function test_skala_bulan_satu_bulan_penuh(): void
    {
        $dari = today()->startOfMonth();
        $sampai = today()->endOfMonth();

        $this->assertSame('2026-06-01', $dari->toDateString());
        $this->assertSame('2026-06-30', $sampai->toDateString());
    }

    public function test_geser_maju_mundur_per_skala(): void
    {
        $dari = Carbon::parse('2026-06-15');

        $this->assertSame('2026-06-16', Timeline::geser($dari, 'hari', 1));
        $this->assertSame('2026-06-14', Timeline::geser($dari, 'hari', -1));
        $this->assertSame('2026-06-22', Timeline::geser($dari, 'minggu', 1));
        $this->assertSame('2026-06-08', Timeline::geser($dari, 'minggu', -1));
        $this->assertSame('2026-07-15', Timeline::geser($dari, 'bulan', 1));
        $this->assertSame('2026-05-15', Timeline::geser($dari, 'bulan', -1));
    }

    public function test_ini_sekarang_benar_kalau_mencakup_hari_ini(): void
    {
        $this->assertTrue(Timeline::iniSekarang(today(), today()));
        $this->assertTrue(Timeline::iniSekarang(today()->subDays(2), today()->addDays(2)));
        $this->assertFalse(Timeline::iniSekarang(today()->subDays(10), today()->subDays(5)));
        $this->assertFalse(Timeline::iniSekarang(today()->addDays(5), today()->addDays(10)));
    }

    public function test_label_rentang_satu_hari_vs_beberapa_hari(): void
    {
        $this->assertSame(today()->translatedFormat('d M Y'), Timeline::label(today(), today()));
        $this->assertSame('15 Jun – 21 Jun 2026', Timeline::label(Carbon::parse('2026-06-15'), Carbon::parse('2026-06-21')));
    }
}

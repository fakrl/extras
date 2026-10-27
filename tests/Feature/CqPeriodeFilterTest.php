<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CQ/CR: kalender dihapus di semua role, diganti filter periode (angka) +
 * navigasi timeline (jadwal/agenda) + agenda. CR.1 memisahkan dua pola:
 * rentang angka (Periode, SA dashboard saja) dan navigasi timeline (Timeline,
 * kartu Jadwal di 5 halaman).
 */
class CqPeriodeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Senin.
        $this->travelTo('2026-06-15 10:00:00');
    }

    private function proyek(array $attr = []): CastingProject
    {
        // client_id eksplisit - supaya proyek tidak nyasar ikut tanpaPicClient() (konteks beda, bukan yang diuji di sini).
        return CastingProject::factory()->create(['client_request_status' => 'disetujui', 'client_id' => User::factory()->state(['role' => 'client']), ...$attr]);
    }

    // CR.1.2: agenda SA mengikuti navigasi timeline (bukan lagi Periode), hanya tanggal berkegiatan dalam rentang.
    public function test_agenda_sa_mengikuti_navigasi_timeline(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $p = $this->proyek(['nama_produksi' => 'Iklan Sabun']);
        $p->shootingDates()->create(['tanggal' => '2026-06-16']);

        $label = Carbon::parse('2026-06-16')->translatedFormat('l, d F Y');
        // default timeline = Minggu ini (15-21 Jun), mencakup 16 Jun.
        $r = $this->actingAs($sa)->get(route('super-admin.dashboard'));
        $r->assertOk()->assertSee('Iklan Sabun')->assertSee($label);

        // pindah ke bulan lain -> di luar rentang, tidak tampil.
        $r2 = $this->actingAs($sa)->get(route('super-admin.dashboard', ['skala' => 'bulan', 'mulai' => '2026-08-01']));
        $r2->assertOk()->assertDontSee('Iklan Sabun')->assertSee('Tidak ada kegiatan di periode ini.');
    }

    // CR.1.3: default skala timeline per halaman.
    public function test_default_timeline_per_halaman(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $client = User::factory()->create(['role' => 'client']);
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id]);

        $this->assertSame('minggu', $this->actingAs($admin)->get(route('admin.dashboard'))->viewData('skala'));
        $this->assertSame('minggu', $this->actingAs($korlap)->get(route('admin.dashboard'))->viewData('skala'));
        $this->assertSame('minggu', $this->actingAs($client)->get(route('client.dashboard'))->viewData('skala'));
        $this->assertSame('minggu', $this->actingAs($extras)->get(route('extras.dashboard'))->viewData('skala'));
        $this->assertSame('hari', $this->actingAs($admin)->get(route('admin.attendance.index'))->viewData('skala'));
        $this->assertSame('minggu', $this->actingAs($sa)->get(route('super-admin.dashboard'))->viewData('skala'));
    }

    // CQ.2.2: kalender lama + CSS-nya sudah tidak ada di 5 halaman yang diganti.
    public function test_tidak_ada_lagi_markup_kalender_lama(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $client = User::factory()->create(['role' => 'client']);
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id]);

        $halaman = [
            fn () => $this->actingAs($sa)->get(route('super-admin.dashboard')),
            fn () => $this->actingAs($admin)->get(route('admin.dashboard')),
            fn () => $this->actingAs($client)->get(route('client.dashboard')),
            fn () => $this->actingAs($extras)->get(route('extras.dashboard')),
            fn () => $this->actingAs($admin)->get(route('admin.attendance.index')),
        ];

        foreach ($halaman as $i => $buka) {
            $html = $buka()->assertOk()->getContent();
            foreach (['jadwal-cal', 'cal-day', 'cal-nav', 'dialog-kalender', 'x-jadwal-calendar'] as $jejak) {
                $this->assertStringNotContainsString($jejak, $html, "halaman #$i masih memuat '$jejak'");
            }
        }
    }

    // CR.1.2: tiap tanggal di agenda Absensi adalah tautan pindah tanggal (?project=&tanggal=);
    // skala diperlebar ke minggu supaya kedua tanggal (hari ini & besok) sama-sama tampil.
    public function test_absensi_bisa_pindah_tanggal_lewat_link_agenda(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Film Senja']);
        $hariIni = $p->shootingDates()->create(['tanggal' => '2026-06-15']);
        $besok = $p->shootingDates()->create(['tanggal' => '2026-06-16']);

        $r = $this->actingAs($admin)->get(route('admin.attendance.index', ['skala' => 'minggu']));
        $r->assertOk();
        $this->assertSame($hariIni->id, $r->viewData('shootingDate')->id);

        $link = route('admin.attendance.index', ['project' => $p->id, 'tanggal' => $besok->id]);
        $r->assertSee(e($link), false);

        $r2 = $this->actingAs($admin)->get($link);
        $this->assertSame($besok->id, $r2->viewData('shootingDate')->id);
    }

    // CR.1.3: default Hari ini, tapi tetap diperluas supaya mencakup tanggal yang sedang dipilih via ?tanggal= walau di luar skala.
    public function test_absensi_default_mencakup_tanggal_dipilih_di_luar_rentang(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek Jauh']);
        $jauh = $p->shootingDates()->create(['tanggal' => '2026-07-20']);

        $r = $this->actingAs($admin)->get(route('admin.attendance.index', ['project' => $p->id, 'tanggal' => $jauh->id]));

        $r->assertOk();
        $this->assertSame('hari', $r->viewData('skala'));
        $this->assertTrue($r->viewData('agendaEvents')->pluck('nama_produksi')->contains('Proyek Jauh'));
    }

    // CR.1.1/CQ.3: pilihan rentang angka SA diingat per halaman di session.
    public function test_sesi_mengingat_pilihan_periode_per_halaman(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->get(route('super-admin.dashboard', ['periode' => 'bulan_ini']));
        $r = $this->actingAs($sa)->get(route('super-admin.dashboard'));

        $this->assertSame('bulan_ini', $r->viewData('preset'));
    }

    // AZ.1: agenda generik tidak boleh membocorkan lokasi proyek yang belum lolos ke Extras
    // (nama proyek sendiri boleh tampil di kartu Pendaftaran Saya - itu bukan pelanggaran AZ.1).
    public function test_agenda_extras_tidak_bocorkan_lokasi_proyek_belum_lolos(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        $profil = ExtrasProfile::create(['user_id' => $extras->id]);
        $p = $this->proyek(['nama_produksi' => 'Proyek Rahasia']);
        $p->shootingDates()->create(['tanggal' => '2026-06-16', 'lokasi' => 'Lokasi Rahasia']);
        $p->applications()->create(['extras_id' => $profil->id, 'status_partisipasi' => 'diajukan']);

        $r = $this->actingAs($extras)->get(route('extras.dashboard'))->assertOk()->assertDontSee('Lokasi Rahasia');
        $this->assertTrue($r->viewData('agendaEvents')->isEmpty());
    }
}

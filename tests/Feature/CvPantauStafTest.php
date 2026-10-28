<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\KorlapRingkasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CV.1: Pantau Admin digabung ke Monitoring (akordeon per orang), tugas Korlap, Ingatkan Korlap.
 */
class CvPantauStafTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-10 09:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_rute_pantau_admin_lama_redirect_ke_monitoring_admin(): void
    {
        $this->actingAs($this->sa)->get('/super-admin/pantau-admin')
            ->assertRedirect('/super-admin/monitoring/admin');
    }

    public function test_menu_pantau_admin_hilang_dari_sidebar(): void
    {
        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Pantau Admin');
    }

    public function test_admin_tanpa_tugas_tetap_tampil(): void
    {
        User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Admin Kosong']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'));

        $r->assertOk()->assertSee('Admin Kosong')->assertSee('Tidak ada tugas');
    }

    public function test_daftar_admin_memuat_jumlah_proyek_tugas_dan_umur_tertua(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek Tugas']);
        $profil = ExtrasProfile::factory()->create();
        ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'diajukan']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'));
        $baris = $r->viewData('admins')->firstWhere('id', $admin->id);

        $this->assertSame(1, $baris->proyek_dipegang);
        $this->assertSame(1, $baris->jumlah_tugas);
        $r->assertSee('Proyek Tugas');
    }

    public function test_klik_nama_menampilkan_tugas_dikelompokkan_dengan_tombol_ingatkan(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Dedi Admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Series Kampus']);
        foreach (range(1, 3) as $i) {
            $profil = ExtrasProfile::factory()->create();
            $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'kontrak_ditandatangani']);
            $app->payment()->create(['status' => 'belum_dibayar']);
        }

        $r = $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'));

        $r->assertOk()->assertSee('Series Kampus')->assertSee('3 Extras')->assertSee('Ingatkan')->assertSee('Ambil alih sebagai Admin');
    }

    // Reviewer (CR): tahapan($a) dipanggil tanpa override $maks sempat memotong 8 item TERTUA per
    // tahap SEBELUM filter perlu=true - tugas perlu yang lebih baru jadi hilang. Harus tetap ketahuan.
    public function test_tugas_perlu_tidak_tersembunyi_di_balik_delapan_lamaran_tertua_tahap_sama(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        $klien = User::factory()->create(['role' => 'client']);

        foreach (range(1, 8) as $i) {
            $this->travel(1)->minutes();
            $p = CastingProject::factory()->create(['admin_id' => $pic->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
            $profil = ExtrasProfile::factory()->create();
            ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'nego_fee']);
        }

        $this->travel(1)->minutes();
        $pTerbaru = CastingProject::factory()->create(['admin_id' => $pic->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek Counter']);
        $profilTerbaru = ExtrasProfile::factory()->create();
        $appTerbaru = ProjectApplication::create(['casting_project_id' => $pTerbaru->id, 'extras_id' => $profilTerbaru->id, 'status_partisipasi' => 'nego_fee']);
        $appTerbaru->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'extras', 'nominal' => 200000, 'aksi' => 'counter']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'));

        $baris = $r->viewData('admins')->firstWhere('id', $pic->id);
        $this->assertNotNull($baris, 'Admin tidak tampil di Monitoring');
        $this->assertSame(1, $baris->jumlah_tugas, 'tugas perlu tersembunyi di balik 8 lamaran tertua non-perlu');
        $this->assertTrue($baris->kelompok_proyek->contains(fn ($grup) => $grup->contains(fn ($g) => $g['proyek'] === 'Proyek Counter')));
    }

    private function proyekKorlap(User $korlap): CastingProject
    {
        $klien = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek Lapangan']);
        $p->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $this->sa->id, 'status_log' => 'berjalan']);

        return $p;
    }

    public function test_tugas_korlap_selfie_menunggu_lebih_dari_dua_jam(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif', 'name' => 'Korlap Budi']);
        $p = $this->proyekKorlap($korlap);
        $sd = $p->shootingDates()->create(['tanggal' => today()]);
        $profil = ExtrasProfile::factory()->create();
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'lolos']);

        $this->travel(-3)->hours();
        Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'dicatat_oleh' => $profil->user_id, 'foto_path' => 'x.jpg', 'status_validasi' => 'menunggu']);
        $this->travel(3)->hours();

        $r = $this->actingAs($this->sa)->get(route('super-admin.monitoring.korlap'));
        $baris = $r->viewData('korlaps')->firstWhere('id', $korlap->id);

        $this->assertSame(1, $baris->jumlah_tugas);
        $r->assertSee('Korlap Budi')->assertSee('Selfie menunggu validasi');
    }

    public function test_tugas_korlap_peserta_belum_diabsen_hari_ini(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif', 'name' => 'Korlap Sari']);
        $p = $this->proyekKorlap($korlap);
        $p->shootingDates()->create(['tanggal' => today()]);
        $profil = ExtrasProfile::factory()->create();
        ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'lolos']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.monitoring.korlap'));
        $baris = $r->viewData('korlaps')->firstWhere('id', $korlap->id);

        $this->assertSame(1, $baris->jumlah_tugas);
        $r->assertSee('Peserta belum diabsen');
    }

    public function test_ingatkan_korlap_terkirim_hanya_ke_korlap_itu_dan_batas_24_jam(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $korlapLain = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $p = $this->proyekKorlap($korlap);
        $p->shootingDates()->create(['tanggal' => today()]);
        $profil = ExtrasProfile::factory()->create();
        ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'lolos']);

        // proyek_id dikirim STRING, persis seperti hidden input form HTML browser beneran
        // (bukan int) - regresi bug 422 akibat perbandingan strict int vs string.
        $this->actingAs($this->sa)->post(route('super-admin.monitoring.korlap.ingatkan'), [
            'korlap_id' => $korlap->id, 'proyek_id' => (string) $p->id, 'jenis' => 'absen_belum',
        ])->assertSessionHas('status');

        $this->assertSame(1, $korlap->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $this->assertSame(0, $korlapLain->notifications()->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'REMIND_KORLAP']);

        $this->actingAs($this->sa)->post(route('super-admin.monitoring.korlap.ingatkan'), [
            'korlap_id' => $korlap->id, 'proyek_id' => (string) $p->id, 'jenis' => 'absen_belum',
        ])->assertSessionHas('error');
        $this->assertSame(1, $korlap->notifications()->where('data->jenis', 'pengingat_sa')->count());

        $this->travelTo('2026-06-11 10:00:00');
        $this->actingAs($this->sa)->post(route('super-admin.monitoring.korlap.ingatkan'), [
            'korlap_id' => $korlap->id, 'proyek_id' => (string) $p->id, 'jenis' => 'absen_belum',
        ])->assertSessionHas('status');
        $this->assertSame(2, $korlap->notifications()->where('data->jenis', 'pengingat_sa')->count());
    }

    /** Regresi N+1: tugasTertunda() harus query O(1), bukan scaling linear per Korlap (lihat bug CV sebelumnya). */
    public function test_tugas_tertunda_tidak_n_plus_satu(): void
    {
        $buat = function (int $n) {
            return collect(range(1, $n))->map(function () {
                $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
                $p = $this->proyekKorlap($korlap);
                $p->shootingDates()->create(['tanggal' => today()]);
                $profil = ExtrasProfile::factory()->create();
                ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'lolos']);

                return $korlap;
            });
        };

        $korlaps8 = $buat(8);
        DB::enableQueryLog();
        KorlapRingkasan::tugasTertunda($korlaps8, null);
        $q8 = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $korlaps16 = $korlaps8->concat($buat(8));
        DB::enableQueryLog();
        KorlapRingkasan::tugasTertunda($korlaps16, null);
        $q16 = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(20, $q8, "Query untuk 8 Korlap: {$q8}");
        $this->assertLessThanOrEqual($q8 + 5, $q16, "Query tidak boleh scaling linear: 8 Korlap={$q8}, 16 Korlap={$q16}");
    }

    public function test_kpi_dan_kartu_dashboard_gabung_admin_korlap(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $profilAdmin = ExtrasProfile::factory()->create();
        $appAdmin = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profilAdmin->id, 'status_partisipasi' => 'kontrak_ditandatangani']);
        $appAdmin->payment()->create(['status' => 'belum_dibayar']);

        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $pk = $this->proyekKorlap($korlap);
        $pk->shootingDates()->create(['tanggal' => today()]);
        $profilKorlap = ExtrasProfile::factory()->create();
        ProjectApplication::create(['casting_project_id' => $pk->id, 'extras_id' => $profilKorlap->id, 'status_partisipasi' => 'lolos']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $this->assertSame(2, $r->viewData('stafTertunda'));
        $r->assertSee('Tugas staf tertunda')->assertSee('Staf perlu diingatkan');
    }
}

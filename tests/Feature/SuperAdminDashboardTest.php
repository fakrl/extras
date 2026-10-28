<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CR.2: dashboard Super Admin diringkas - 4 KPI + Butuh keputusan Anda (maks 5)
 * + Admin perlu diingatkan (maks 5) + Jadwal. Detail lengkap dipindah ke
 * /super-admin/persetujuan dan /super-admin/pantau-admin (lihat test terpisah).
 */
class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-15 10:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    private function proyek(array $tanggal, string $req = 'disetujui', array $attr = []): CastingProject
    {
        $p = CastingProject::factory()->create(['client_request_status' => $req, ...$attr]);
        foreach ($tanggal as $t) {
            $p->shootingDates()->create(['tanggal' => $t]);
        }

        return $p;
    }

    private function aplikasi(CastingProject $p, string $status = 'kontrak_ditandatangani', string $nama = 'Extras'): ProjectApplication
    {
        $profil = ExtrasProfile::factory()->create();
        $profil->user->update(['name' => $nama]);

        return ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => $status]);
    }

    public function test_empat_kpi_menaut_ke_halaman_yang_benar(): void
    {
        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertOk()
            ->assertSee('Menunggu keputusan')
            ->assertSee('Tugas staf tertunda')
            ->assertSee('Proyek berjalan')
            ->assertSee('Invoice belum lunas')
            ->assertSee(e(route('super-admin.persetujuan')), false)
            ->assertSee(e(route('super-admin.monitoring.admin')), false)
            ->assertSee(e(route('admin.projects.index', ['tahap' => 'berjalan'])), false);
    }

    public function test_kpi_menunggu_keputusan_tidak_ikut_hitung_akun_perhatian(): void
    {
        $this->proyek([], 'menunggu_acc');
        CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'alasan_tolak' => 'Lengkapi lokasi']);
        $this->proyek(['2026-06-20'], 'disetujui', ['admin_id' => null, 'client_id' => null]);
        User::factory()->create(['role' => 'client', 'wajib_ganti_password' => true, 'updated_at' => now()->subDays(10)]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $this->assertSame(3, $r->viewData('menungguKeputusan'));
        $this->assertSame(4, $r->viewData('jumlahTindakan'));
    }

    public function test_proyek_berjalan_kpi_ikut_rentang_angka(): void
    {
        $this->proyek(['2026-06-14', '2026-06-16'], 'disetujui', ['nama_produksi' => 'Berjalan Dalam']);
        $this->proyek(['2026-01-10', '2026-01-12'], 'disetujui', ['nama_produksi' => 'Luar Rentang']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['periode' => '30']));

        $this->assertSame(1, $r->viewData('proyekBerjalan'));
    }

    public function test_invoice_belum_lunas_kpi(): void
    {
        $p = $this->proyek([]);
        $p->invoices()->create(['nominal' => 1000000, 'status_bayar' => 'lunas', 'dibayar_at' => '2026-03-10 09:00:00']);
        $this->proyek([])->invoices()->create(['nominal' => 7000000]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertOk()->assertSee('Rp 7.000.000');
        $this->assertSame(1, $r->viewData('invoiceBelumLunas')->count());
    }

    // CO.1: sengketa pembayaran adalah tugas Admin (CM.3.4), bukan tugas SA - tidak boleh muncul di dashboard SA.
    public function test_dashboard_tidak_memuat_sengketa_pembayaran(): void
    {
        $p = $this->proyek(['2026-06-20'], 'disetujui', ['nama_produksi' => 'Film Senja']);
        $app = $this->aplikasi($p, 'kontrak_ditandatangani', 'Sari Sengketa');
        Payment::create(['project_application_id' => $app->id, 'status' => 'disengketakan', 'alasan_sengketa' => 'Nominal kurang']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertOk()
            ->assertDontSee('Pembayaran disengketakan')
            ->assertDontSee('Sari Sengketa')
            ->assertDontSee('Nominal kurang');
    }

    public function test_butuh_keputusan_maksimal_5_urut_paling_lama_dan_kosong(): void
    {
        $r0 = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $r0->assertSee('Tidak ada yang perlu diputuskan.');

        foreach (range(1, 7) as $i) {
            $this->travel(1)->minutes();
            CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'nama_produksi' => "Ajuan $i"]);
        }

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $butuh = $r->viewData('butuhKeputusan');

        $this->assertCount(5, $butuh);
        $this->assertSame(['Ajuan 1', 'Ajuan 2', 'Ajuan 3', 'Ajuan 4', 'Ajuan 5'], $butuh->pluck('req.nama_produksi')->all());
    }

    public function test_tombol_tinjau_tetapkan_pic_dan_lihat_akun_pakai_rute_lama(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $pengajuan = $this->proyek([], 'menunggu_acc', ['nama_produksi' => 'Iklan Kopi']);
        $tanpaPic = $this->proyek(['2026-06-20'], 'disetujui', ['nama_produksi' => 'Tanpa PIC', 'admin_id' => null]);
        $akun = User::factory()->create(['role' => 'client', 'name' => 'Client Lama', 'wajib_ganti_password' => true]);
        User::whereKey($akun->id)->update(['updated_at' => now()->subDays(10)]);

        $html = $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertOk()
            ->assertSee(route('super-admin.projects.acc', $pengajuan), false)
            ->assertSee(route('super-admin.projects.minta-revisi', $pengajuan), false)
            ->assertSee(route('super-admin.projects.reject', $pengajuan), false)
            ->assertSee(route('super-admin.projects.tetapkan-pic', $tanpaPic), false)
            ->assertSee(route('super-admin.admins.show', $akun), false)
            ->getContent();

        $this->assertStringContainsString('Tinjau', $html);
        $this->assertStringContainsString('Tetapkan PIC', $html);
        $this->assertStringContainsString('Lihat akun', $html);

        $this->from(route('super-admin.dashboard'))->patch(route('super-admin.projects.tetapkan-pic', $tanpaPic), ['admin_id' => $admin->id])
            ->assertRedirect(route('super-admin.dashboard'));
        $this->assertSame($admin->id, $tanpaPic->fresh()->admin_id);
    }

    // CW.1: kartu "Staf perlu diingatkan" = satu baris per ORANG, bukan per kelompok - sini
    // 1 Admin punya tugas di 2 proyek berbeda (2 kelompok CU.7) tapi harus muncul 1 baris digabung.
    public function test_admin_perlu_diingatkan_maksimal_5_dan_kosong(): void
    {
        $r0 = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $r0->assertSee('Semua Admin dan Korlap sesuai jadwal.');

        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        foreach (range(1, 2) as $i) {
            $p = CastingProject::factory()->create(['admin_id' => $pic->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
            $profil = ExtrasProfile::factory()->create();
            ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'diajukan']);
        }

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $diingatkan = $r->viewData('diingatkan');
        $this->assertCount(1, $diingatkan, 'tugas di 2 proyek milik 1 Admin harus digabung jadi 1 baris');
        $this->assertSame($pic->id, $diingatkan->first()['staf']->id);
        $this->assertSame(2, $diingatkan->first()['jumlah']);
        $this->assertCount(2, $diingatkan->first()['kelompok']);
        $r->assertSee('Ingatkan');
    }

    // Reviewer: KPI harus hitung total lengkap tugas perlu per Admin, bukan hasil take(5) tampilan.
    public function test_kpi_tugas_admin_tertunda_hitung_lengkap_bukan_dipotong_lima(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        foreach (range(1, 7) as $i) {
            $this->travel(1)->minutes();
            $p = CastingProject::factory()->create(['admin_id' => $pic->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
            $profil = ExtrasProfile::factory()->create();
            ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'diajukan']);
        }

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $this->assertSame(7, $r->viewData('pantauTotal'));
        $this->assertSame(7, $r->viewData('stafTertunda'));
        $r->assertSee('>7<', false);
    }

    public function test_jadwal_navigasi_timeline_dan_agenda(): void
    {
        $p = $this->proyek(['2026-06-16'], 'disetujui', ['nama_produksi' => 'Jadwal Minggu Ini']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertOk()->assertSee('Jadwal Minggu Ini')->assertSee('Minggu ini');
        $this->assertSame('minggu', $r->viewData('skala'));
    }

    // CR.2: elemen lama yang dikeluarkan dari dashboard tidak boleh muncul lagi.
    public function test_elemen_lama_tidak_ada_lagi_di_dashboard(): void
    {
        $this->proyek(['2026-06-14', '2026-06-16']);

        $html = $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertOk()->getContent();

        foreach (['Status Proyek', 'Total saat ini', 'Honor staf belum dibayar', '(semua waktu, tidak ikut filter)', 'href="#perlu-tindakan"', 'sa-tab', 'sa-role-grid'] as $jejak) {
            $this->assertStringNotContainsString($jejak, $html, "dashboard masih memuat '$jejak'");
        }
    }

    public function test_agenda_berisi_jumlah_extras_absensi_tanpa_xss(): void
    {
        $p = CastingProject::factory()->create(['client_request_status' => 'disetujui', 'nama_produksi' => '<img src=x onerror=alert(1)>']);
        $tgl = $p->shootingDates()->create(['tanggal' => '2026-06-16', 'lokasi' => 'Studio "A"']);
        $hadir = $this->aplikasi($p);
        $this->aplikasi($p);
        Attendance::create(['project_application_id' => $hadir->id, 'event_shooting_date_id' => $tgl->id, 'status' => 'hadir', 'dicatat_oleh' => $this->sa->id]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertOk()->assertSee('1/2 hadir')
            ->assertDontSee('<img src=x', false)
            ->assertSee(e(route('admin.attendance.index', ['project' => $p->id, 'tanggal' => $tgl->id])), false)
            ->assertSee(e(route('admin.projects.show', $p)), false);
    }
}

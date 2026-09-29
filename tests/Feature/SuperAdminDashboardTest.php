<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\ProjectExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BD.3: dashboard Super Admin (ringkasan).
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

    public function test_filter_custom_menghitung_uang_dari_tanggal_transaksi(): void
    {
        $p = $this->proyek([]);
        $p->invoices()->create(['nominal' => 1000000, 'status_bayar' => 'lunas', 'dibayar_at' => '2026-03-10 09:00:00']);
        $p->invoices()->create(['nominal' => 7000000, 'status_bayar' => 'lunas', 'dibayar_at' => '2026-05-01 09:00:00']);
        ProjectExpense::create(['casting_project_id' => $p->id, 'label' => 'Konsumsi', 'nominal' => 250000, 'tanggal' => '2026-03-20', 'created_by' => $this->sa->id]);
        ProjectExpense::create(['casting_project_id' => $p->id, 'label' => 'Lain', 'nominal' => 333000, 'tanggal' => '2026-04-02', 'created_by' => $this->sa->id]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['dari' => '2026-03-01', 'sampai' => '2026-03-31']));

        $r->assertOk()->assertSee('31 hari')->assertSee('Proyek difilter pakai tanggal shooting, uang pakai tanggal transaksi');
        $uang = $r->viewData('uang');
        $this->assertEqualsWithDelta(1000000, $uang->total_masuk, 0.01);
        $this->assertEqualsWithDelta(250000, $uang->total_keluar, 0.01);
        $this->assertEqualsWithDelta(750000, $uang->saldo, 0.01);
        $r->assertSee('Rp 750.000')->assertDontSee('Rp 7.000.000')->assertDontSee('Rp 333.000');
    }

    public function test_preset_dan_parameter_rusak_jatuh_ke_bulan_ini(): void
    {
        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['dari' => 'x', 'sampai' => '2026-01-01', 'periode' => 'ngawur']));

        $r->assertOk()->assertSee('30 hari');
        $this->assertSame('bulan', $r->viewData('preset'));

        $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['periode' => 'tahun']))->assertSee('365 hari');
    }

    public function test_empat_kartu_status_proyek_angka_benar(): void
    {
        $this->proyek([], 'menunggu_acc');
        $this->proyek(['2026-06-20']);
        $this->proyek(['2026-06-14', '2026-06-16']);
        $this->proyek(['2026-06-10']);
        $this->proyek(['2026-06-02', '2026-06-03']);
        $this->proyek(['2025-01-10']);
        $this->proyek(['2026-06-25'], 'ditolak');

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $this->assertSame(
            ['menunggu_acc' => 1, 'mendatang' => 1, 'berjalan' => 1, 'selesai' => 2],
            $r->viewData('statusProyek')->all()
        );
        $r->assertSee(route('admin.projects.index', ['tahap' => 'berjalan']), false);
    }

    public function test_perlu_tindakan_tampilkan_sengketa_dan_invoice_belum_lunas(): void
    {
        $p = $this->proyek(['2026-06-20'], 'disetujui', ['nama_produksi' => 'Film Senja']);
        $app = $this->aplikasi($p, 'kontrak_ditandatangani', 'Sari Sengketa');
        Payment::create(['project_application_id' => $app->id, 'status' => 'disengketakan', 'alasan_sengketa' => 'Nominal kurang']);
        $p->classes()->create(['nama_kelas' => 'A', 'budget_client' => 150000, 'kuota_kelas' => 4]);
        $p->invoices()->create([]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertSee('Pembayaran disengketakan')->assertSee('Sari Sengketa')->assertSee('Nominal kurang')
            ->assertSee(route('payments.show', $app), false)
            ->assertSee('Invoice belum lunas')->assertSee('Film Senja')->assertSee('Rp 600.000')
            ->assertDontSee('Semua aman')->assertDontSee('sa-perlu is-aman');
    }

    public function test_acc_dan_tolak_dari_dashboard_tetap_jalan(): void
    {
        $a = $this->proyek([], 'menunggu_acc', ['nama_produksi' => 'Iklan Kopi', 'status' => 'ditutup']);
        $b = $this->proyek([], 'menunggu_acc', ['status' => 'ditutup']);

        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))
            ->assertSee('Iklan Kopi')
            ->assertSee(route('super-admin.projects.acc', $a), false)
            ->assertSee(route('super-admin.projects.reject', $b), false)
            ->assertSee('tolak-req-'.$b->id, false);

        $this->from(route('super-admin.dashboard'))->patch(route('super-admin.projects.acc', $a))
            ->assertRedirect(route('super-admin.dashboard'));
        $this->from(route('super-admin.dashboard'))->patch(route('super-admin.projects.reject', $b), ['alasan_tolak' => 'Budget kurang'])
            ->assertRedirect(route('super-admin.dashboard'));

        $this->assertSame('disetujui', $a->fresh()->client_request_status);
        $this->assertSame('ditolak', $b->fresh()->client_request_status);
    }

    public function test_kalender_berisi_jumlah_extras_absensi_dan_aksi_tanpa_xss(): void
    {
        $p = CastingProject::factory()->create(['client_request_status' => 'disetujui', 'nama_produksi' => '<img src=x onerror=alert(1)>']);
        $tgl = $p->shootingDates()->create(['tanggal' => '2026-06-16', 'lokasi' => 'Studio "A"']);
        $hadir = $this->aplikasi($p);
        $this->aplikasi($p);
        Attendance::create(['project_application_id' => $hadir->id, 'event_shooting_date_id' => $tgl->id, 'status' => 'hadir', 'dicatat_oleh' => $this->sa->id]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertOk()->assertSee('1\/2 hadir', false)
            ->assertDontSee('<img src=x', false)
            ->assertSee(e(json_encode(route('admin.attendance.index', ['project' => $p->id, 'tanggal' => $tgl->id]))), false)
            ->assertSee(e(json_encode(route('admin.projects.show', $p))), false);

        $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['bulan' => '2026-07']))
            ->assertOk()->assertDontSee('1\/2 hadir', false)->assertSee('bulan=2026-06', false);
    }

    public function test_akun_per_role_dan_client_belum_ganti_password(): void
    {
        User::factory()->count(2)->create(['role' => 'client', 'wajib_ganti_password' => true]);
        User::factory()->create(['role' => 'client']);
        $this->proyek(['2026-06-14', '2026-06-18'], 'disetujui', ['nama_produksi' => 'Sinetron Pagi']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $this->assertSame(3, (int) $r->viewData('akunPerRole')['client']);
        $r->assertSee('2 akun Client baru belum ganti password')
            ->assertSee('Sinetron Pagi')
            ->assertDontSee('Margin Bulan Ini')
            ->assertDontSee('Honor Berjalan (Top 5)');
    }
}

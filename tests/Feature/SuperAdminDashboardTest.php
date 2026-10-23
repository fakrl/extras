<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
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

    public function test_dashboard_dua_angka_honor_staf_dan_invoice_belum_lunas(): void
    {
        $p = $this->proyek([]);
        $p->invoices()->create(['nominal' => 1000000, 'status_bayar' => 'lunas', 'dibayar_at' => '2026-03-10 09:00:00']);
        $this->proyek([])->invoices()->create(['nominal' => 7000000]);
        $asg = $p->adminAssignments()->create(['user_id' => User::factory()->create(['role' => 'korlap'])->id, 'assigned_by' => $this->sa->id, 'status_log' => 'selesai']);
        StaffPayroll::create(['admin_project_assignment_id' => $asg->id, 'nominal_pokok' => 250000]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['dari' => '2026-03-01', 'sampai' => '2026-03-31']));

        $r->assertOk()->assertSee('31 hari')
            ->assertSee('Honor staf belum dibayar')->assertSee('Rp 250.000')
            ->assertSee('Invoice belum lunas (1)')
            ->assertDontSee('Uang Periode')->assertDontSee('Proyeksi')->assertDontSee('Piutang');

    }

    public function test_preset_dan_parameter_rusak_jatuh_ke_default(): void
    {
        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['dari' => 'x', 'sampai' => '2026-01-01', 'periode' => 'ngawur']));

        $r->assertOk()->assertSee('30 hari');
        $this->assertSame('30', $r->viewData('preset'));

        $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['periode' => '365']))->assertSee('365 hari');
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

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['dari' => '2026-06-01', 'sampai' => '2026-06-30']));

        $this->assertSame(
            ['menunggu_acc' => 1, 'mendatang' => 1, 'berjalan' => 1, 'selesai' => 2],
            $r->viewData('statusProyek')->all()
        );
        $r->assertSee(route('admin.projects.index', ['tahap' => 'berjalan']), false);
    }

    // CO.1: sengketa pembayaran adalah tugas Admin (CM.3.4), bukan tugas SA - tidak boleh muncul di Perlu Tindakan SA.
    public function test_perlu_tindakan_tidak_memuat_sengketa_pembayaran(): void
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

    public function test_acc_dan_tolak_dari_dashboard_tetap_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $a = $this->proyek([], 'menunggu_acc', ['nama_produksi' => 'Iklan Kopi', 'status' => 'ditutup']);
        $b = $this->proyek([], 'menunggu_acc', ['status' => 'ditutup']);
        $a->classes()->create(['nama_kelas' => 'Ibu-ibu', 'budget_client' => 1, 'kuota_kelas' => 1]);
        $a->shootingDates()->create(['tanggal' => today()->addDays(5)]);

        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))
            ->assertSee('Iklan Kopi')
            ->assertSee(route('super-admin.projects.acc', $a), false)
            ->assertSee(route('super-admin.projects.reject', $b), false)
            ->assertSee('tolak-req-'.$b->id, false);

        $this->from(route('super-admin.dashboard'))->patch(route('super-admin.projects.acc', $a), ['admin_id' => $admin->id])
            ->assertRedirect(route('super-admin.dashboard'));
        $this->from(route('super-admin.dashboard'))->patch(route('super-admin.projects.reject', $b), ['alasan_tolak' => 'Budget kurang'])
            ->assertRedirect(route('super-admin.dashboard'));

        $this->assertSame('disetujui', $a->fresh()->client_request_status);
        $this->assertSame('ditolak', $b->fresh()->client_request_status);
    }

    public function test_agenda_berisi_jumlah_extras_absensi_dan_aksi_tanpa_xss(): void
    {
        $p = CastingProject::factory()->create(['client_request_status' => 'disetujui', 'nama_produksi' => '<img src=x onerror=alert(1)>']);
        $tgl = $p->shootingDates()->create(['tanggal' => '2026-06-16', 'lokasi' => 'Studio "A"']);
        $hadir = $this->aplikasi($p);
        $this->aplikasi($p);
        Attendance::create(['project_application_id' => $hadir->id, 'event_shooting_date_id' => $tgl->id, 'status' => 'hadir', 'dicatat_oleh' => $this->sa->id]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['periode' => '+7']));

        $r->assertOk()->assertSee('1/2 hadir')
            ->assertDontSee('<img src=x', false)
            ->assertSee(e(route('admin.attendance.index', ['project' => $p->id, 'tanggal' => $tgl->id])), false)
            ->assertSee(e(route('admin.projects.show', $p)), false);

        // CQ.2: kegiatan di luar rentang filter tidak tampil (7 hari terakhir dari 15 Juni tidak mencakup 16 Juni).
        $this->actingAs($this->sa)->get(route('super-admin.dashboard', ['periode' => '7']))
            ->assertOk()->assertDontSee('1/2 hadir');
    }

    // CO.1.4: "akun perlu perhatian SA" = Client belum ganti password > 7 hari (updated_at, bukan sekadar flag-nya).
    public function test_akun_per_role_dan_akun_perlu_perhatian_lebih_dari_7_hari(): void
    {
        $lama = User::factory()->count(2)->create(['role' => 'client', 'wajib_ganti_password' => true]);
        User::whereIn('id', $lama->pluck('id'))->update(['updated_at' => now()->subDays(10)]);
        User::factory()->create(['role' => 'client', 'wajib_ganti_password' => true]); // baru dibuat, belum 7 hari
        User::factory()->create(['role' => 'client']);
        $this->proyek(['2026-06-14', '2026-06-18'], 'disetujui', ['nama_produksi' => 'Sinetron Pagi']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $this->assertSame(4, (int) $r->viewData('akunPerRole')['client']);
        $this->assertSame(2, $r->viewData('akunPerhatian'));
        $r->assertSee('2 akun Client')
            ->assertSee('Belum ganti password lebih dari 7 hari')
            ->assertSee('Sinetron Pagi')
            ->assertDontSee('Margin Bulan Ini')
            ->assertDontSee('Honor Berjalan (Top 5)');
    }
}

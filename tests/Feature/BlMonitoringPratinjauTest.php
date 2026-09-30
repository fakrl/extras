<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SPEC BL: pratinjau Monitoring Admin/Korlap, masuk mode dengan tujuan, keluar balik ke pratinjau.
 */
class BlMonitoringPratinjauTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    private User $admin;

    private CastingProject $proyek;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->sa = User::factory()->create(['role' => 'super_admin']);
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Rina']);
        $this->proyek = CastingProject::create([
            'admin_id' => $this->admin->id,
            'nama_produksi' => 'Proyek Senja',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
            'status' => 'dibuka',
            'client_request_status' => 'disetujui',
        ]);
    }

    private function aplikasi(string $status): ProjectApplication
    {
        $u = User::factory()->create(['role' => 'extras']);
        $e = ExtrasProfile::create(['user_id' => $u->id, 'nama_asli' => $u->name, 'nik' => (string) random_int(1e15, 9e15)]);

        return ProjectApplication::create(['casting_project_id' => $this->proyek->id, 'extras_id' => $e->id, 'status_partisipasi' => $status, 'fee_final' => 200000]);
    }

    private function isiData(): void
    {
        $nego = $this->aplikasi('nego_fee');
        $nego->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar']);
        $nego->feeNegotiations()->create(['round' => 2, 'diajukan_oleh' => 'extras', 'nominal' => 250000, 'aksi' => 'counter']);
        $negoDibalas = $this->aplikasi('nego_fee');
        $negoDibalas->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar']);
        $this->aplikasi('deal');
        $lolos = $this->aplikasi('lolos');
        $lolos->contract()->create([]);
        $lolos->payment()->create(['status' => 'belum_dibayar']);

        $sd = $this->proyek->shootingDates()->create(['tanggal' => today(), 'lokasi' => 'Studio 5', 'jam_mulai' => '07:00']);
        Attendance::create(['project_application_id' => $lolos->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'dicatat_oleh' => $lolos->extras->user_id, 'foto_path' => 'attendances/x.jpg', 'status_validasi' => 'menunggu']);
        $lolos->tambahCatatan($this->admin, 'sanksi', 'Telat datang 30 menit');
    }

    public function test_pratinjau_hanya_untuk_super_admin(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap']);
        foreach (['super-admin.monitoring.admin', 'super-admin.monitoring.korlap'] as $r) {
            $this->actingAs($this->sa)->get(route($r))->assertOk();
            $this->actingAs($this->admin)->get(route($r))->assertForbidden();
            $this->actingAs($korlap)->get(route($r))->assertForbidden();
        }
    }

    public function test_angka_kartu_sama_dengan_dashboard_admin(): void
    {
        $this->isiData();

        $pratinjau = $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'))->assertOk()
            ->assertSee('Admin Rina')->assertSee('Proyek Senja')->assertSee('Masuk mode Admin');
        $dashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Nego menunggu balasan Admin');

        $kartu = $pratinjau->viewData('ringkasan');
        $this->assertSame($kartu, $dashboard->viewData('ringkasan'));
        $this->assertSame([1, 1, 1, 1], [$kartu['nego']['jumlah'], $kartu['deal']['jumlah'], $kartu['kontrak']['jumlah'], $kartu['bayar']['jumlah']]);
        $this->assertSame(2, $pratinjau->viewData('admins')->firstWhere('id', $this->admin->id)->menunggu);
        $this->assertSame($dashboard->viewData('chartStatusPembayaran')['data'][0], $kartu['bayar']['jumlah']);
    }

    public function test_angka_absensi_sama_dengan_halaman_absensi(): void
    {
        $this->isiData();

        $pratinjau = $this->actingAs($this->sa)->get(route('super-admin.monitoring.korlap'))->assertOk()
            ->assertSee('Studio 5')->assertSee('0/2 hadir')->assertSee('1 menunggu validasi')
            ->assertSee('Telat datang 30 menit')->assertSee('Masuk mode Korlap');
        $sd = $pratinjau->viewData('hari')['Hari ini']->first();
        $absensi = $this->actingAs($this->sa)->get(route('admin.attendance.index', ['project' => $this->proyek->id, 'tanggal' => $sd->id]))->assertOk();

        $this->assertSame($absensi->viewData('rekap'), $sd->rekap);
        $this->assertCount(1, $pratinjau->viewData('menunggu'));
    }

    public function test_tanpa_shooting_tampil_tanggal_terdekat(): void
    {
        $this->proyek->shootingDates()->create(['tanggal' => today()->addDays(5)]);

        $this->actingAs($this->sa)->get(route('super-admin.monitoring.korlap'))->assertOk()
            ->assertSee('Tidak ada shooting hari ini')
            ->assertSee(today()->addDays(5)->translatedFormat('d M Y'));
    }

    public function test_get_pratinjau_murni_baca(): void
    {
        $this->isiData();
        $hitung = fn () => collect(['contracts', 'payments', 'notifications', 'activity_logs', 'attendances', 'invoices'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
        $awal = $hitung();

        $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'))->assertOk();
        $this->actingAs($this->sa)->get(route('super-admin.monitoring.korlap'))->assertOk();

        $this->assertSame($awal, $hitung());
    }

    public function test_masuk_mode_dengan_tujuan_lalu_keluar_balik_ke_pratinjau(): void
    {
        $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), [
            'mode' => 'admin', 'ke' => '/admin/projects?peserta=nego_fee', 'kembali' => '/super-admin/monitoring/admin',
        ])->assertRedirect('/admin/projects?peserta=nego_fee')->assertSessionHas('sa_mode', 'admin');

        $this->post(route('super-admin.mode.keluar'))->assertRedirect('/super-admin/monitoring/admin')->assertSessionMissing('sa_kembali');

        $this->assertDatabaseHas('activity_logs', ['action' => 'SA_MODE_MULAI', 'user_id' => $this->sa->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'SA_MODE_KELUAR', 'user_id' => $this->sa->id]);
    }

    public function test_tujuan_luar_diabaikan(): void
    {
        foreach (['https://evil.test', '//evil.test', '/\\evil.test', 'javascript:alert(1)', '/super-admin/akun'] as $ke) {
            $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), ['mode' => 'korlap', 'ke' => $ke, 'kembali' => 'https://evil.test'])
                ->assertRedirect('/admin/absensi')->assertSessionMissing('sa_kembali');
            $this->post(route('super-admin.mode.keluar'))->assertRedirect(route('super-admin.dashboard'));
        }
    }

    public function test_submenu_monitoring(): void
    {
        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertOk()
            ->assertSee('href="'.route('super-admin.monitoring.admin').'"', false)
            ->assertSee('href="'.route('super-admin.monitoring.korlap').'"', false)
            ->assertSee('href="'.route('super-admin.mode.pilih', 'client').'"', false)
            ->assertSee('href="'.route('super-admin.mode.pilih', 'extras').'"', false)
            ->assertDontSee('href="'.route('super-admin.mode.pilih', 'admin').'"', false);

        $this->actingAs($this->sa)->get(route('super-admin.mode.pilih', 'korlap'))->assertRedirect(route('super-admin.monitoring.korlap'));
    }
}

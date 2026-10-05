<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** SPEC BZ: dashboard Korlap = ringkasan monitoring, dibatasi proyek penugasan. */
class BzDashboardKorlapTest extends TestCase
{
    use RefreshDatabase;

    private User $korlap;

    private User $sa;

    private CastingProject $milik;

    private CastingProject $lain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sa = User::factory()->create(['role' => 'super_admin']);
        $this->korlap = User::factory()->create(['role' => 'korlap']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->milik = $this->proyek($admin, 'Proyek Milik Saya', 'Lokasi Milik');
        $this->lain = $this->proyek($admin, 'Proyek Orang Lain', 'Lokasi Lain');
        AdminProjectAssignment::create(['casting_project_id' => $this->milik->id, 'user_id' => $this->korlap->id, 'assigned_by' => $this->sa->id]);
        AdminProjectAssignment::create(['casting_project_id' => $this->lain->id, 'user_id' => User::factory()->create(['role' => 'korlap'])->id, 'assigned_by' => $this->sa->id]);
    }

    private function proyek(User $admin, string $nama, string $lokasi): CastingProject
    {
        $p = CastingProject::create(['admin_id' => $admin->id, 'nama_produksi' => $nama, 'deadline' => now()->addDays(7), 'kuota' => 5, 'status' => 'dibuka', 'client_request_status' => 'disetujui']);
        $sd = $p->shootingDates()->create(['tanggal' => today(), 'lokasi' => $lokasi, 'jam_mulai' => '07:00']);
        $u = User::factory()->create(['role' => 'extras', 'name' => 'Extras '.$nama]);
        $e = ExtrasProfile::create(['user_id' => $u->id, 'nama_asli' => $u->name, 'nik' => (string) random_int(1e15, 9e15)]);
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $e->id, 'status_partisipasi' => 'lolos', 'fee_final' => 200000]);
        Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'dicatat_oleh' => $u->id, 'foto_path' => 'attendances/x.jpg', 'status_validasi' => 'menunggu']);
        $app->tambahCatatan($admin, 'catatan', 'Catatan '.$nama);

        return $p;
    }

    public function test_korlap_hanya_melihat_proyek_penugasannya(): void
    {
        $r = $this->actingAs($this->korlap)->get(route('admin.dashboard'))->assertOk();

        $r->assertSee('Proyek Milik Saya')->assertSee('Lokasi Milik')->assertSee('Extras Proyek Milik Saya')->assertSee('Catatan Proyek Milik Saya')
            ->assertDontSee('Proyek Orang Lain')->assertDontSee('Lokasi Lain')->assertDontSee('Extras Proyek Orang Lain')->assertDontSee('Catatan Proyek Orang Lain')
            ->assertDontSee('Sebagai Koordinator Lapangan');
        $this->assertCount(1, $r->viewData('hari')['Hari ini']);
        $this->assertCount(1, $r->viewData('menunggu'));
        $this->assertCount(1, $r->viewData('catatan'));
    }

    public function test_shooting_terdekat_tidak_bocor(): void
    {
        DB::table('event_shooting_dates')->where('casting_project_id', $this->milik->id)->delete();
        $this->lain->shootingDates()->create(['tanggal' => today()->addDay()]);
        $this->lain->shootingDates()->create(['tanggal' => today()->addDays(3)]);

        $r = $this->actingAs($this->korlap)->get(route('admin.dashboard'))->assertOk();
        $this->assertNull($r->viewData('terdekat'));
    }

    public function test_baris_menuju_halaman_absensi_bukan_form_mode_sa(): void
    {
        $sd = $this->milik->shootingDates()->first();
        $url = route('admin.attendance.index', ['project' => $this->milik->id, 'tanggal' => $sd->id], false);

        $this->actingAs($this->korlap)->get(route('admin.dashboard'))
            ->assertSee('href="'.e($url).'"', false)
            ->assertSee('href="'.e($url).'#app-', false)
            ->assertDontSee('mon-masuk')->assertDontSee('name="ke"', false);
    }

    public function test_korlap_tanpa_penugasan_lihat_pesan_kosong(): void
    {
        $baru = User::factory()->create(['role' => 'korlap']);

        $this->actingAs($baru)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Belum ada proyek yang ditugaskan kepadamu')
            ->assertDontSee('Proyek Milik Saya')->assertDontSee('Proyek Orang Lain');
    }

    public function test_sa_godmode_mode_korlap_dan_monitoring_tetap_semua(): void
    {
        $this->actingAs($this->sa)->withSession(['sa_mode' => 'korlap'])->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Proyek Milik Saya')->assertSee('Proyek Orang Lain')->assertDontSee('Belum ada proyek');

        $this->actingAs($this->sa)->get(route('super-admin.monitoring.korlap'))->assertOk()
            ->assertSee('Proyek Milik Saya')->assertSee('Proyek Orang Lain')
            ->assertSee('Catatan Proyek Orang Lain')->assertSee('Extras Proyek Orang Lain')
            ->assertSee('id="mon-masuk"', false)->assertSee('name="ke"', false);
    }
}

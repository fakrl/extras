<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\AdminRingkasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** SPEC BP: step-bar tahapan kandidat + daftar siapa & aksi berikutnya. */
class BpTahapanKandidatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CastingProject $proyek;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->proyek = CastingProject::create([
            'admin_id' => $this->admin->id, 'nama_produksi' => 'Proyek Senja', 'deadline' => now()->addDays(7),
            'kuota' => 5, 'status' => 'dibuka', 'client_request_status' => 'disetujui',
        ]);
    }

    private function aplikasi(string $status, string $username, int $jamLalu = 1): ProjectApplication
    {
        $u = User::factory()->create(['role' => 'extras', 'username' => $username]);
        $e = ExtrasProfile::create(['user_id' => $u->id, 'nama_asli' => $u->name, 'nik' => (string) random_int(1e15, 9e15)]);
        $a = ProjectApplication::create(['casting_project_id' => $this->proyek->id, 'extras_id' => $e->id, 'status_partisipasi' => $status, 'fee_final' => 200000]);
        DB::table('project_applications')->where('id', $a->id)->update(['updated_at' => now()->subHours($jamLalu)]);

        return $a->fresh();
    }

    private function isiData(): void
    {
        $this->aplikasi('diajukan', 'ajuan1');
        $nego = $this->aplikasi('nego_fee', 'negoextras');
        $nego->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar']);
        $nego->feeNegotiations()->create(['round' => 2, 'diajukan_oleh' => 'extras', 'nominal' => 200000, 'aksi' => 'counter']);
        $this->aplikasi('nego_fee', 'negoadmin')->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar']);
        $this->aplikasi('deal', 'deal1');
        $this->aplikasi('deal', 'deal2');
        $this->aplikasi('diajukan_ke_client', 'client1');
        $this->aplikasi('lolos', 'ttdadmin')->contract()->create([]);
        $this->aplikasi('lolos', 'ttdextras')->contract()->create(['ttd_admin_signature_path' => 'x.png']);
        $belum = $this->aplikasi('kontrak_ditandatangani', 'belumtf');
        $belum->contract()->create(['ttd_admin_signature_path' => 'x.png', 'ttd_extras_signature_path' => 'y.png', 'signed_at' => now()->subDay()]);
        $belum->payment()->create(['status' => 'belum_dibayar']);
        $this->aplikasi('kontrak_ditandatangani', 'sudahtf')->payment()->create(['status' => 'ditransfer', 'ditransfer_at' => now()]);
        $this->aplikasi('selesai_produksi', 'tuntas')->payment()->create(['status' => 'dikonfirmasi_diterima']);
        $this->aplikasi('ditolak', 'ditolak1');
    }

    private function item(array $tahapan, string $tahap, string $username): array
    {
        return $tahapan[$tahap]['daftar']->first(fn ($i) => $i['app']->extras->user->username === $username);
    }

    public function test_step_bar_angka_perlu_kamu_dan_teks_aksi(): void
    {
        $this->isiData();
        $t = AdminRingkasan::tahapan();

        $this->assertSame(['Ajuan', 'Nego Fee', 'Dipilih Client', 'Kontrak', 'Selesai'], array_column($t, 'label'));
        $this->assertSame([1, 4, 1, 2, 2], array_column($t, 'jumlah'));
        $this->assertSame([1, 3, 0, 1, 1], array_column($t, 'perlu'));

        $cek = fn ($tahap, $u, $teks, $perlu) => tap($this->item($t, $tahap, $u), function ($i) use ($teks, $perlu) {
            $this->assertSame($teks, $i['teks']);
            $this->assertSame($perlu, $i['perlu']);
        });
        $cek('ajuan', 'ajuan1', 'Belum direview — beri grade / tolak', true);
        $cek('nego', 'negoextras', 'Extras counter Rp 200.000 — balas', true);
        $cek('nego', 'negoadmin', 'Menunggu balasan Extras', false);
        $cek('nego', 'deal1', 'Siap diajukan ke Client', true);
        $cek('client', 'client1', 'Menunggu keputusan Client', false);
        $cek('kontrak', 'ttdadmin', 'TTD Admin belum', true);
        $cek('kontrak', 'ttdextras', 'Tunggu TTD Extras', false);
        $cek('selesai', 'belumtf', 'Honor belum ditransfer', true);
        $cek('selesai', 'sudahtf', 'Menunggu konfirmasi Extras', false);

        $nego = $this->item($t, 'nego', 'negoextras')['app'];
        $this->assertSame(route('admin.negotiations.show', $nego, false), $this->item($t, 'nego', 'negoextras')['url']);
        $this->assertStringEndsWith('#app-'.$this->item($t, 'nego', 'deal1')['app']->id, $this->item($t, 'nego', 'deal1')['url']);
    }

    public function test_angka_konsisten_dengan_ringkasan(): void
    {
        $this->isiData();
        $t = AdminRingkasan::tahapan();
        $r = AdminRingkasan::untuk();

        $nego = $t['nego']['daftar']->where('perlu', true);
        $this->assertSame($r['nego']['jumlah'], $nego->where('app.status_partisipasi', 'nego_fee')->count());
        $this->assertSame($r['deal']['jumlah'], $nego->where('app.status_partisipasi', 'deal')->count());
        $this->assertSame($r['kontrak']['jumlah'], $t['kontrak']['perlu']);
    }

    public function test_daftar_maks_urut_paling_lama(): void
    {
        foreach (range(1, 10) as $n) {
            $this->aplikasi('diajukan', "antri{$n}", $n);
        }
        $daftar = AdminRingkasan::tahapan()['ajuan']['daftar'];

        $this->assertCount(8, $daftar);
        $this->assertSame('antri10', $daftar->first()['app']->extras->user->username);
        $this->assertSame('antri3', $daftar->last()['app']->extras->user->username);
    }

    public function test_dashboard_render_tab_default_dan_tanpa_funnel_lama(): void
    {
        $this->isiData();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('funnel-step')
            ->assertSee('Tahapan Partisipasi Kandidat')
            ->assertSee('3 perlu kamu')
            ->assertSee('id="tahap-tab-nego" aria-controls="tahap-panel-nego" aria-selected="true"', false)
            ->assertSee('id="tahap-panel-ajuan" aria-labelledby="tahap-tab-ajuan"  hidden', false)
            ->assertSee('@negoextras')
            ->assertSee($this->proyek->kode_proyek)
            ->assertSee('Extras counter Rp 200.000 — balas')
            ->assertSee('Lihat semua di tahap ini')
            ->assertDontSee('@tuntas')
            ->assertDontSee('@ditolak1');
    }

    public function test_tahap_kosong(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Nggak ada kandidat di tahap ini.');
    }

    public function test_pratinjau_monitoring_pakai_tahapan_sama(): void
    {
        $this->isiData();
        $sa = User::factory()->create(['role' => 'super_admin']);

        $res = $this->actingAs($sa)->get(route('super-admin.monitoring.admin'))->assertOk()->assertSee('3 perlu kamu');
        $this->assertSame(array_column(AdminRingkasan::tahapan(), 'perlu'), array_column($res->viewData('tahapan'), 'perlu'));
    }
}

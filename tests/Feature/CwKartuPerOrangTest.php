<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CW: kartu "Staf perlu diingatkan" dashboard SA diringkas jadi 1 baris per ORANG
 * (gabung semua kelompok tugas Admin/Korlap itu), tombol Ingatkan kirim 1 notifikasi
 * + 1 ActivityLog rangkum semua kelompok. Dedup key lama (pg-/pa-) & Monitoring TIDAK disentuh.
 */
class CwKartuPerOrangTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-10 09:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    private function tugasHonorBelumTransfer(User $admin, CastingProject $p): ProjectApplication
    {
        $extras = ExtrasProfile::factory()->create();
        $app = ProjectApplication::create([
            'casting_project_id' => $p->id, 'extras_id' => $extras->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
        ]);
        $app->payment()->create(['status' => 'belum_dibayar']);

        return $app;
    }

    private function tugasReviewAwal(User $admin, CastingProject $p): ProjectApplication
    {
        $extras = ExtrasProfile::factory()->create();

        return ProjectApplication::create([
            'casting_project_id' => $p->id, 'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);
    }

    private function proyekKorlap(User $korlap, string $nama = 'Proyek Lapangan'): CastingProject
    {
        $klien = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => $nama]);
        $p->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $this->sa->id, 'status_log' => 'berjalan']);

        return $p;
    }

    // Admin dengan tugas beda JENIS di 2 proyek berbeda harus tetap digabung jadi 1 baris.
    public function test_satu_baris_per_admin_gabung_lintas_proyek_dan_jenis(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        $klien = User::factory()->create(['role' => 'client']);
        $p1 = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek A']);
        $p2 = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek B']);

        $this->tugasReviewAwal($admin, $p1);
        $this->tugasHonorBelumTransfer($admin, $p2);
        $this->tugasHonorBelumTransfer($admin, $p2);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $diingatkan = $r->viewData('diingatkan');

        $this->assertCount(1, $diingatkan, 'satu Admin dengan tugas di >1 proyek/jenis harus jadi 1 baris');
        $baris = $diingatkan->first();
        $this->assertSame($admin->id, $baris['staf']->id);
        $this->assertSame('admin', $baris['peran']);
        $this->assertSame(3, $baris['jumlah']);
        $this->assertCount(2, $baris['kelompok']);
        $r->assertSee('Proyek A', false)->assertSee('Proyek B', false);
    }

    // Korlap dengan 2 jenis tugas (selfie + absen belum) di proyek berbeda digabung jadi 1 baris.
    public function test_satu_baris_per_korlap_gabung_lintas_proyek(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif', 'name' => 'Budi Korlap']);

        // Proyek 1: selfie menunggu validasi > 2 jam.
        $p1 = $this->proyekKorlap($korlap, 'Lapangan Satu');
        $sd1 = $p1->shootingDates()->create(['tanggal' => today()]);
        $profil1 = ExtrasProfile::factory()->create();
        $app1 = ProjectApplication::create(['casting_project_id' => $p1->id, 'extras_id' => $profil1->id, 'status_partisipasi' => 'lolos']);
        $this->travel(-3)->hours();
        Attendance::create(['project_application_id' => $app1->id, 'event_shooting_date_id' => $sd1->id, 'status' => 'hadir', 'dicatat_oleh' => $profil1->user_id, 'foto_path' => 'x.jpg', 'status_validasi' => 'menunggu']);
        $this->travel(3)->hours();

        // Proyek 2: peserta belum diabsen hari ini.
        $p2 = $this->proyekKorlap($korlap, 'Lapangan Dua');
        $p2->shootingDates()->create(['tanggal' => today()]);
        ProjectApplication::create(['casting_project_id' => $p2->id, 'extras_id' => ExtrasProfile::factory()->create()->id, 'status_partisipasi' => 'lolos']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $diingatkan = $r->viewData('diingatkan');

        $this->assertCount(1, $diingatkan);
        $baris = $diingatkan->first();
        $this->assertSame($korlap->id, $baris['staf']->id);
        $this->assertSame('korlap', $baris['peran']);
        $this->assertCount(2, $baris['kelompok']);
    }

    // CW.1.2: tombol Ingatkan per baris kirim SATU notifikasi + SATU ActivityLog rangkum semua kelompok.
    public function test_ingatkan_orang_kirim_satu_notifikasi_satu_log_rangkum_semua_kelompok(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        $klien = User::factory()->create(['role' => 'client']);
        $p1 = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek A']);
        $p2 = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Proyek B']);
        $this->tugasReviewAwal($admin, $p1);
        $this->tugasHonorBelumTransfer($admin, $p2);

        // Form POST asli selalu kirim string, bukan int - disimulasikan eksplisit di sini.
        $this->actingAs($this->sa)->post(route('super-admin.dashboard.ingatkan-staf'), [
            'peran' => 'admin',
            'staf_id' => (string) $admin->id,
        ])->assertSessionHas('status');

        $this->assertSame(1, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $notif = $admin->notifications()->where('data->jenis', 'pengingat_sa')->first();
        $this->assertStringContainsString('Proyek A', $notif->data['pesan']);
        $this->assertStringContainsString('Proyek B', $notif->data['pesan']);
        $this->assertStringContainsString('Honor belum ditransfer', $notif->data['pesan']);

        $this->assertSame(1, ActivityLog::where('action', 'REMIND_STAF')->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'REMIND_STAF',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
        ]);
    }

    public function test_ingatkan_orang_korlap_kirim_ke_korlap_itu_saja(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $lain = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $p = $this->proyekKorlap($korlap);
        $p->shootingDates()->create(['tanggal' => today()]);
        ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => ExtrasProfile::factory()->create()->id, 'status_partisipasi' => 'lolos']);

        $this->actingAs($this->sa)->post(route('super-admin.dashboard.ingatkan-staf'), [
            'peran' => 'korlap',
            'staf_id' => (string) $korlap->id,
        ])->assertSessionHas('status');

        $this->assertSame(1, $korlap->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $this->assertSame(0, $lain->notifications()->where('data->jenis', 'pengingat_sa')->count());
    }

    // Batas 24 jam berlaku untuk BATCH gabungan (per orang), bukan per kelompok.
    public function test_ingatkan_orang_batas_24_jam(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $this->tugasHonorBelumTransfer($admin, $p);

        $this->actingAs($this->sa)->post(route('super-admin.dashboard.ingatkan-staf'), [
            'peran' => 'admin', 'staf_id' => (string) $admin->id,
        ])->assertSessionHas('status');

        $this->actingAs($this->sa)->post(route('super-admin.dashboard.ingatkan-staf'), [
            'peran' => 'admin', 'staf_id' => (string) $admin->id,
        ])->assertSessionHas('error');
        $this->assertSame(1, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $r->assertSee('Sudah diingatkan', false);

        $this->travelTo(now()->addDay()->addMinute());
        $this->actingAs($this->sa)->post(route('super-admin.dashboard.ingatkan-staf'), [
            'peran' => 'admin', 'staf_id' => (string) $admin->id,
        ])->assertSessionHas('status');
        $this->assertSame(2, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());
    }

    // Gerbang CM: endpoint baru HANYA Super Admin.
    public function test_ingatkan_orang_hanya_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $this->tugasHonorBelumTransfer($admin, $p);

        foreach (['admin', 'korlap', 'client', 'extras'] as $role) {
            $u = $role === 'admin' ? $admin : User::factory()->create(['role' => $role]);
            $this->actingAs($u)->post(route('super-admin.dashboard.ingatkan-staf'), [
                'peran' => 'admin', 'staf_id' => (string) $admin->id,
            ])->assertForbidden();
        }
    }

    // Dedup key lama 'pa-{appId}' (badge "Diingatkan SA" dashboard Admin individual) TETAP jalan,
    // tidak terpengaruh key baru 'pall-{peran}-{id}'.
    public function test_badge_pa_individual_tetap_jalan_setelah_fitur_ingatkan_orang(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $app = $this->tugasHonorBelumTransfer($admin, $p);

        $this->actingAs($this->sa)->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id])
            ->assertSessionHas('status');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('Diingatkan SA');
    }

    // Halaman Monitoring ▸ Admin/Korlap TIDAK berubah oleh fitur ini (tetap rinci per kelompok).
    public function test_monitoring_admin_tidak_berubah(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p1 = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $p2 = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $this->tugasReviewAwal($admin, $p1);
        $this->tugasHonorBelumTransfer($admin, $p2);

        $rp = $this->actingAs($this->sa)->get(route('super-admin.monitoring.admin'));
        $baris = $rp->viewData('admins')->firstWhere('id', $admin->id);

        $this->assertSame(2, $baris->jumlah_tugas);
        $this->assertCount(2, $baris->kelompok_proyek);
    }
}

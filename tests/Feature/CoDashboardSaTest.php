<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CO: Super Admin memantau & mengingatkan Admin. Konten grup pengajuan/Pantau
 * Admin lengkap pindah ke /super-admin/persetujuan & /super-admin/pantau-admin
 * sejak CR.2/CR.3 - lihat SuperAdminPersetujuanTest & SuperAdminPantauAdminTest.
 */
class CoDashboardSaTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-10 09:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    private function tugasTertunda(User $admin): ProjectApplication
    {
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $profil = ExtrasProfile::factory()->create();

        return ProjectApplication::create([
            'casting_project_id' => $p->id,
            'extras_id' => $profil->id,
            'status_partisipasi' => 'diajukan',
        ]);
    }

    // CO.2: Ingatkan -> notif ke PIC saja, log REMIND_ADMIN, maks 1x/24 jam.
    public function test_ingatkan_admin_notif_pic_saja_dan_batas_24_jam(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'name' => 'Rina PIC']);
        $lain = User::factory()->create(['role' => 'admin', 'name' => 'Yoga Lain']);
        $app = $this->tugasTertunda($pic);

        $this->actingAs($this->sa)
            ->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id])
            ->assertRedirect();

        $this->assertSame(1, $pic->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $this->assertSame(0, $lain->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'REMIND_ADMIN',
            'subject_type' => ProjectApplication::class,
            'subject_id' => $app->id,
        ]);

        // kedua kalinya dalam 24 jam: ditolak, tidak ada notif baru.
        $this->actingAs($this->sa)
            ->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id])
            ->assertSessionHas('error');
        $this->assertSame(1, $pic->notifications()->where('data->jenis', 'pengingat_sa')->count());

        // setelah >24 jam, boleh lagi.
        $this->travelTo(now()->addDay()->addMinute());
        $this->actingAs($this->sa)
            ->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id])
            ->assertSessionHas('status');
        $this->assertSame(2, $pic->notifications()->where('data->jenis', 'pengingat_sa')->count());
    }

    // CR.2.4: dashboard SA juga menampilkan baris ringkas + tombol Ingatkan (bukan cuma halaman Pantau Admin).
    public function test_dashboard_tandai_sudah_diingatkan_setelah_dikirim(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $app = $this->tugasTertunda($pic);

        $this->actingAs($this->sa)->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $r->assertSee('Sudah diingatkan');
    }

    // CO.2.3: tanda "Diingatkan SA" di dashboard Admin untuk tugas yang baru diingatkan.
    public function test_tanda_diingatkan_sa_di_dashboard_admin(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $app = $this->tugasTertunda($pic);
        $this->actingAs($this->sa)->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id]);

        $this->actingAs($pic)->get(route('admin.dashboard'))->assertSee('Diingatkan SA');
    }

    // CO.2.4: titik merah muncul untuk Admin dengan pengingat belum dibaca, hilang setelah dibaca.
    public function test_dot_merah_muncul_dan_hilang_setelah_dibaca(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $app = $this->tugasTertunda($pic);
        $this->actingAs($this->sa)->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id]);

        $this->actingAs($pic)->get(route('admin.dashboard'))->assertSee('Ada tugas tertunda', false);

        $pic->unreadNotifications()->update(['read_at' => now()]);
        ProjectApplication::whereKey($app->id)->update(['status_partisipasi' => 'ditolak']);

        $this->actingAs($pic)->get(route('admin.dashboard'))->assertDontSee('Ada tugas tertunda', false);
    }

    // Bug fix: Admin yang hanya ditugaskan (admin_project_assignments), bukan PIC,
    // tidak boleh ikut melihat tugas proyek itu di baris Pantau Admin miliknya.
    public function test_pantau_admin_hanya_tampil_di_baris_pic_bukan_yang_ditugaskan(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        $ditugaskan = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Yoga Ditugaskan']);
        $app = $this->tugasTertunda($pic);
        $app->castingProject->adminAssignments()->create(['user_id' => $ditugaskan->id, 'assigned_by' => $this->sa->id, 'status_log' => 'berjalan']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));

        $pantauAdmin = $r->viewData('pantauAdmin');
        $baris = fn (User $u) => $pantauAdmin->first(fn ($p) => $p['admin']->id === $u->id);

        $this->assertNotNull($baris($pic));
        $this->assertSame([$app->id], $baris($pic)['tugas']->pluck('app_id')->all());
        $this->assertNull($baris($ditugaskan));
    }

    // CO.3.3: log aktivitas memuat @username pelaku.
    public function test_log_aktivitas_memuat_username(): void
    {
        $this->sa->update(['username' => 'fakrul']);
        ActivityLog::record('APPROVE_PROJECT_REQUEST', 'Contoh aksi', null, [], $this->sa);

        $this->actingAs($this->sa)->get(route('super-admin.activity-logs'))->assertSee('@fakrul');
    }
}

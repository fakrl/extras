<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CV.2: Daftar Proyek SA selalu semua, filter PIC/Korlap/Client/tindakan, urut, tab Riwayat. */
class CvDaftarProyekTest extends TestCase
{
    use RefreshDatabase;

    public function test_sa_tidak_melihat_pilihan_saya_semua_tapi_admin_melihat_di_panel_filter(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);

        $this->actingAs($sa)->get(route('admin.projects.index'))
            ->assertOk()->assertDontSee('Proyek saya')->assertDontSee('Semua proyek (lihat saja)');

        $this->actingAs($admin)->get(route('admin.projects.index'))
            ->assertOk()->assertSee('Semua proyek (lihat saja)');
    }

    public function test_sa_selalu_lihat_semua_proyek_tanpa_toggle(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $lain = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'nama_produksi' => 'Punya Admin']);
        CastingProject::factory()->create(['admin_id' => $lain->id, 'client_id' => $klien->id, 'nama_produksi' => 'Punya Lain']);

        $this->actingAs($sa)->get(route('admin.projects.index'))
            ->assertSee('Punya Admin')->assertSee('Punya Lain');
    }

    public function test_filter_pic_korlap_client(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $picLain = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $klienLain = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $pic->id, 'client_id' => $klien->id, 'nama_produksi' => 'Proyek Target']);
        $p->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $sa->id, 'status_log' => 'berjalan']);
        CastingProject::factory()->create(['admin_id' => $picLain->id, 'client_id' => $klienLain->id, 'nama_produksi' => 'Proyek Lain']);

        $this->actingAs($sa)->get(route('admin.projects.index', ['admin_id' => $pic->id]))
            ->assertSee('Proyek Target')->assertDontSee('Proyek Lain');
        $this->actingAs($sa)->get(route('admin.projects.index', ['korlap_id' => $korlap->id]))
            ->assertSee('Proyek Target')->assertDontSee('Proyek Lain');
        $this->actingAs($sa)->get(route('admin.projects.index', ['client_id' => $klien->id]))
            ->assertSee('Proyek Target')->assertDontSee('Proyek Lain');
    }

    public function test_chip_filter_bisa_dihapus(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Admin Chip']);

        $r = $this->actingAs($sa)->get(route('admin.projects.index', ['admin_id' => $pic->id]));

        // Chip "PIC: Admin Chip" terlihat, dan link hapusnya kembali ke index tanpa admin_id.
        $r->assertSee('PIC: Admin Chip')
            ->assertSee('href="'.e(route('admin.projects.index')).'"', false);
    }

    public function test_perlu_tindakan_saja(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $pPerlu = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Perlu Tindakan']);
        $profil = ExtrasProfile::factory()->create();
        ProjectApplication::create(['casting_project_id' => $pPerlu->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'diajukan']);
        CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Tanpa Tindakan']);

        $this->actingAs($sa)->get(route('admin.projects.index', ['tindakan' => 1]))
            ->assertSee('Perlu Tindakan')->assertDontSee('Tanpa Tindakan');
    }

    public function test_urutkan_nama(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'nama_produksi' => 'Zebra Project']);
        CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'nama_produksi' => 'Awal Project']);

        $r = $this->actingAs($sa)->get(route('admin.projects.index', ['urut' => 'nama']));

        $nama = $r->viewData('projects')->pluck('nama_produksi')->all();
        $this->assertSame(['Awal Project', 'Zebra Project'], $nama);
    }

    public function test_baris_proyek_memuat_pic_dan_korlap(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Admin Baris']);
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif', 'name' => 'Korlap Baris']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $pic->id, 'client_id' => $klien->id]);
        $p->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $sa->id, 'status_log' => 'berjalan']);

        $this->actingAs($sa)->get(route('admin.projects.index'))
            ->assertSee('Admin Baris')->assertSee('Korlap Baris');
    }

    public function test_tab_riwayat_hanya_log_proyek_itu(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id]);
        $pLain = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id]);

        ActivityLog::record('UPDATE_PROJECT_PIC', 'Log milik proyek ini', $p);
        ActivityLog::record('UPDATE_PROJECT_PIC', 'Log milik proyek lain', $pLain);

        $r = $this->actingAs($sa)->get(route('admin.projects.show', [$p, 'tab' => 'riwayat']));

        $r->assertOk()->assertSee('Log milik proyek ini')->assertDontSee('Log milik proyek lain');
    }
}

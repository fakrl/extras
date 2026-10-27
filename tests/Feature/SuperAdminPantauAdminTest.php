<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CR.3.2: halaman detail Pantau Admin (dipindah dari dashboard SA) - reuse
 * partial _pantau-admin apa adanya, semua tugas (tanpa batas 5), filter Admin.
 */
class SuperAdminPantauAdminTest extends TestCase
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

    public function test_semua_tugas_tampil_tanpa_batas_lima(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        foreach (range(1, 7) as $i) {
            $this->travel(1)->minutes();
            $this->tugasTertunda($pic);
        }

        $r = $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index'));

        $r->assertOk();
        $baris = $r->viewData('pantauAdmin')->first(fn ($p) => $p['admin']->id === $pic->id);
        $this->assertCount(7, $baris['tugas']);
    }

    public function test_filter_dropdown_admin(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Rina PIC']);
        $lain = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Yoga Lain']);
        $this->tugasTertunda($pic);
        $this->tugasTertunda($lain);

        $r = $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index', ['admin_id' => $pic->id]));

        $r->assertOk();
        $this->assertSame(['Rina PIC'], $r->viewData('pantauAdmin')->pluck('admin.name')->all());
    }

    public function test_urut_paling_lama_tertunda(): void
    {
        $lama = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Admin Lama']);
        $baru = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Admin Baru']);
        $this->tugasTertunda($lama);
        $this->travel(1)->hours();
        $this->tugasTertunda($baru);

        $r = $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index'));

        $urutan = $r->viewData('pantauAdmin')->pluck('admin.name')->all();
        $this->assertSame(['Admin Lama', 'Admin Baru'], $urutan);
    }

    public function test_tombol_ingatkan_dan_sudah_diingatkan(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $app = $this->tugasTertunda($pic);

        $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index'))->assertSee('Ingatkan');

        $this->actingAs($this->sa)->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id]);

        $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index'))->assertSee('Sudah diingatkan');
    }

    // Reviewer: tahapan($a) dipanggil tanpa override $maks sempat memotong 8 item TERTUA per
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

        $r = $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index'));

        $baris = $r->viewData('pantauAdmin')->first(fn ($p) => $p['admin']->id === $pic->id);
        $this->assertNotNull($baris, 'tugas perlu tersembunyi di balik 8 lamaran tertua non-perlu');
        $this->assertTrue($baris['tugas']->contains(fn ($t) => $t['proyek'] === 'Proyek Counter'));
    }

    public function test_kosong_kalau_tidak_ada_tugas_tertunda(): void
    {
        $this->actingAs($this->sa)->get(route('super-admin.pantau-admin.index'))
            ->assertSee('Tidak ada tugas tertunda di Admin mana pun saat ini.');
    }

    public function test_akses_ditolak_untuk_role_selain_super_admin(): void
    {
        foreach (['admin', 'korlap', 'client', 'extras'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'aktif']);
            $this->actingAs($user)->get(route('super-admin.pantau-admin.index'))->assertForbidden();
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CX.1 - CX.3: Halaman Persetujuan digabung ke dalam Proyek sebagai tab "Menunggu Keputusan" (SA saja).
 * Reuse partial _pengajuan apa adanya, tab per grup dengan hitungan, rute lama redirect, sidebar dot pindah ke Proyek.
 */
class SuperAdminPersetujuanTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-06-15 10:00:00');
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    // CX.1: Tab Menunggu Keputusan di halaman Proyek memuat pengajuan baru vs hasil revisi dengan hitungan.
    public function test_tab_pengajuan_baru_dan_revisi_dengan_hitungan(): void
    {
        $baru = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'nama_produksi' => 'Iklan Baru']);
        $revisi = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'alasan_tolak' => 'Lengkapi lokasi', 'nama_produksi' => 'Iklan Revisi']);

        $r = $this->actingAs($this->sa)->get(route('admin.projects.index', ['tahap' => 'menunggu_acc']));

        $r->assertOk();
        $this->assertSame([$baru->id], $r->viewData('pengajuanBaru')->pluck('id')->all());
        $this->assertSame([$revisi->id], $r->viewData('pengajuanRevisi')->pluck('id')->all());
        $r->assertSee('Menunggu Keputusan')
            ->assertSee('Pengajuan Client menunggu keputusan')
            ->assertSee('Pengajuan sudah direvisi Client')
            ->assertSee('Iklan Baru')
            ->assertSee('Iklan Revisi')
            ->assertSee('Diajukan ulang')
            ->assertSee('role="tablist"', false);
    }

    public function test_tab_tanpa_pic_dan_akun_perlu_perhatian(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $tanpaPic = CastingProject::factory()->create(['client_request_status' => 'disetujui', 'admin_id' => null, 'client_id' => $klien->id, 'nama_produksi' => 'Proyek Tanpa PIC']);
        $akun = User::factory()->create(['role' => 'client', 'name' => 'Client Lama', 'wajib_ganti_password' => true]);
        User::whereKey($akun->id)->update(['updated_at' => now()->subDays(10)]);

        $r = $this->actingAs($this->sa)->get(route('admin.projects.index', ['tahap' => 'menunggu_acc']));

        $r->assertOk()->assertSee('Proyek Tanpa PIC')->assertSee('Client Lama')
            ->assertSee(route('super-admin.projects.tetapkan-pic', $tanpaPic), false)
            ->assertSee(route('super-admin.admins.show', $akun), false);
    }

    public function test_acc_minta_revisi_dan_tolak_dari_halaman_persetujuan_tetap_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $a = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'nama_produksi' => 'Iklan Kopi', 'status' => 'ditutup']);
        $b = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'status' => 'ditutup']);
        $url = route('admin.projects.index', ['tahap' => 'menunggu_acc']);

        $this->actingAs($this->sa)->from($url)->patch(route('super-admin.projects.acc', $a), ['admin_id' => $admin->id])
            ->assertRedirect($url);
        $this->actingAs($this->sa)->from($url)->patch(route('super-admin.projects.reject', $b), ['alasan_tolak' => 'Budget kurang'])
            ->assertRedirect($url);

        $this->assertSame('disetujui', $a->fresh()->client_request_status);
        $this->assertSame('ditolak', $b->fresh()->client_request_status);
    }

    public function test_rute_lama_persetujuan_redirect_ke_lokasi_baru(): void
    {
        $r = $this->actingAs($this->sa)->get(route('super-admin.persetujuan'));
        $r->assertRedirect(route('admin.projects.index', ['tahap' => 'menunggu_acc']));
    }

    public function test_admin_biasa_tidak_melihat_tab_menunggu_keputusan(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'nama_produksi' => 'Iklan Rahasia SA']);

        $r = $this->actingAs($admin)->get(route('admin.projects.index'));
        $r->assertOk()
            ->assertDontSee('Menunggu Keputusan')
            ->assertDontSee('tahap=menunggu_acc');

        // Akses langsung dengan query param juga tidak menampilkan data persetujuan
        $rLangsung = $this->actingAs($admin)->get(route('admin.projects.index', ['tahap' => 'menunggu_acc']));
        $rLangsung->assertOk()
            ->assertDontSee('Menunggu Keputusan')
            ->assertDontSee('Pengajuan Client menunggu keputusan')
            ->assertDontSee('Iklan Rahasia SA');
    }

    public function test_titik_merah_sidebar_pindah_ke_proyek(): void
    {
        CastingProject::factory()->create(['client_request_status' => 'menunggu_acc']);

        $rSa = $this->actingAs($this->sa)->get(route('super-admin.dashboard'));
        $rSa->assertOk();
        // Menu Persetujuan sudah dihapus
        $rSa->assertDontSee('>Persetujuan<', false);
        // Titik merah nempel di menu Proyek untuk Super Admin
        $rSa->assertSee('ti-movie');
        $rSa->assertSee('Ada yang perlu diputuskan');
    }

    public function test_akses_ditolak_untuk_role_selain_super_admin(): void
    {
        foreach (['admin', 'korlap', 'client', 'extras'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'aktif']);
            $this->actingAs($user)->get(route('super-admin.persetujuan'))->assertForbidden();
        }
    }
}

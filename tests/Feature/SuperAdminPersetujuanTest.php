<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CR.3.1: halaman detail Persetujuan (dipindah dari dashboard SA) - reuse
 * partial _pengajuan apa adanya, tab per grup dengan hitungan, role:super_admin murni.
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

    // CO.1: pengajuan baru vs pengajuan hasil revisi dibedakan dan dikelompokkan per tab.
    public function test_tab_pengajuan_baru_dan_revisi_dengan_hitungan(): void
    {
        $baru = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'nama_produksi' => 'Iklan Baru']);
        $revisi = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'alasan_tolak' => 'Lengkapi lokasi', 'nama_produksi' => 'Iklan Revisi']);

        $r = $this->actingAs($this->sa)->get(route('super-admin.persetujuan'));

        $r->assertOk();
        $this->assertSame([$baru->id], $r->viewData('pengajuanBaru')->pluck('id')->all());
        $this->assertSame([$revisi->id], $r->viewData('pengajuanRevisi')->pluck('id')->all());
        $r->assertSee('Pengajuan Client menunggu keputusan')
            ->assertSee('Pengajuan sudah direvisi Client')
            ->assertSee('Iklan Baru')
            ->assertSee('Iklan Revisi')
            ->assertSee('Diajukan ulang')
            ->assertSee('role="tablist"', false);
    }

    public function test_tab_tanpa_pic_dan_akun_perlu_perhatian(): void
    {
        $tanpaPic = CastingProject::factory()->create(['client_request_status' => 'disetujui', 'admin_id' => null, 'nama_produksi' => 'Proyek Tanpa PIC']);
        $akun = User::factory()->create(['role' => 'client', 'name' => 'Client Lama', 'wajib_ganti_password' => true]);
        User::whereKey($akun->id)->update(['updated_at' => now()->subDays(10)]);

        $r = $this->actingAs($this->sa)->get(route('super-admin.persetujuan'));

        $r->assertOk()->assertSee('Proyek Tanpa PIC')->assertSee('Client Lama')
            ->assertSee(route('super-admin.projects.tetapkan-pic', $tanpaPic), false)
            ->assertSee(route('super-admin.admins.show', $akun), false);
    }

    public function test_acc_minta_revisi_dan_tolak_dari_halaman_persetujuan_tetap_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $a = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'nama_produksi' => 'Iklan Kopi', 'status' => 'ditutup']);
        $b = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc', 'status' => 'ditutup']);

        $this->actingAs($this->sa)->from(route('super-admin.persetujuan'))->patch(route('super-admin.projects.acc', $a), ['admin_id' => $admin->id])
            ->assertRedirect(route('super-admin.persetujuan'));
        $this->actingAs($this->sa)->from(route('super-admin.persetujuan'))->patch(route('super-admin.projects.reject', $b), ['alasan_tolak' => 'Budget kurang'])
            ->assertRedirect(route('super-admin.persetujuan'));

        $this->assertSame('disetujui', $a->fresh()->client_request_status);
        $this->assertSame('ditolak', $b->fresh()->client_request_status);
    }

    public function test_akses_ditolak_untuk_role_selain_super_admin(): void
    {
        foreach (['admin', 'korlap', 'client', 'extras'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'aktif']);
            $this->actingAs($user)->get(route('super-admin.persetujuan'))->assertForbidden();
        }
    }
}

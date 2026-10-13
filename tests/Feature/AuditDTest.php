<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Audit 12 Okt 2026, batch D: Arsipkan, mode lihat, Lineup daftar, sidebar, /event token salah. */
class AuditDTest extends TestCase
{
    use RefreshDatabase;

    private function proyek(User $admin, array $attrs = []): CastingProject
    {
        return CastingProject::create(array_merge([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Uji',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ], $attrs));
    }

    public function test_sa_07_aksi_hapus_akun_berlabel_arsipkan_dan_route_tetap(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $staf = User::factory()->create(['role' => 'admin', 'name' => 'Staf Satu']);

        $this->actingAs($sa)->get(route('super-admin.akun.index'))->assertOk()
            ->assertSee('Arsipkan')->assertSee('Arsipkan Staf Satu?')
            ->assertSee('tidak bisa login')->assertSee('dipulihkan')
            ->assertDontSee('Hapus Staf Satu?')
            ->assertSee(route('super-admin.admins.destroy', $staf), false);

        $this->actingAs($sa)->delete(route('super-admin.admins.destroy', $staf));
        $this->assertSoftDeleted($staf);
        $this->assertDatabaseHas('activity_logs', ['action' => 'DELETE_USER', 'subject_id' => $staf->id]);
    }

    public function test_sa_15_16_banner_sticky_dan_tombol_terkunci_berikon_gembok(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($sa)->post(route('super-admin.mode.mulai'), ['mode' => 'client', 'user_id' => $client->id]);
        $html = $this->get(route('client.dashboard'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/\.sa-mode-banner \{[^}]*position: sticky;[^}]*z-index: 250/s', $html);
        $this->assertStringContainsString('ti ti-lock sa-gembok', $html);
        $this->assertStringContainsString('(terkunci, mode lihat saja)', $html);
        $this->assertStringContainsString("document.documentElement.classList.add('sa-lihat-saja')", $html);
    }

    public function test_ad_19_lineup_mode_daftar_lewat_query_dan_bulk_tetap_ada(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $p = $this->proyek($admin);
        $ex = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras', 'username' => 'budi_x'])->id]);
        ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $ex->id, 'status_partisipasi' => 'diajukan']);

        $kartu = $this->actingAs($admin)->get(route('admin.projects.applicants', $p))->assertOk();
        $this->assertStringNotContainsString('xgrid is-daftar', $kartu->getContent());

        $this->get(route('admin.projects.applicants', [$p, 'tampil' => 'daftar']))->assertOk()
            ->assertSee('xgrid is-daftar', false)->assertSee('@budi_x')->assertSee('bulk-check', false)
            ->assertSee('Beri Grade')->assertSee('tampil=daftar', false);
    }

    public function test_ad_38_transition_dimatikan_saat_load_pertama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString("classList.add('no-anim')", $html);
        $this->assertStringContainsString("classList.remove('no-anim')", $html);
        $this->assertStringContainsString('.no-anim, .no-anim * { transition: none !important; }', $html);
    }

    public function test_ex_35_token_salah_ada_pesan_dan_tombol_tanpa_membocorkan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client', 'nama_perusahaan' => 'PT Rahasia Sekali']);
        $p = $this->proyek($admin, ['client_id' => $client->id, 'status' => 'ditutup', 'share_token' => 'tokenditutup123']);

        foreach (['/event/token-ngawur', '/event/'.$p->share_token] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('Pendaftaran sudah tidak dibuka')
                ->assertSee('Lihat lowongan lain')
                ->assertSee(route('home').'#lowongan', false)
                ->assertSee(route('login'), false)->assertSee(route('register'), false)
                ->assertDontSee('PT Rahasia Sekali')->assertDontSee('Proyek Uji');
        }

        $extras = User::factory()->create(['role' => 'extras']);
        $this->actingAs($extras)->get('/event/token-ngawur')->assertOk()
            ->assertSee('Lihat lowongan lain')->assertDontSee('Masuk');
    }
}

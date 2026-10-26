<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Seksi "06 / Proyek yang pernah diikuti": tembok visibilitas Client (tiap Client cuma
 * lihat proyeknya sendiri) + link Lineup/detail sesuai dikelolaOleh() untuk Admin.
 */
class ProfilRiwayatProyekTest extends TestCase
{
    use RefreshDatabase;

    private ExtrasProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'extras', 'username' => 'dimas.rk']);
        $this->profile = ExtrasProfile::factory()->create(['user_id' => $user->id]);
    }

    private function proyek(array $attrs = []): CastingProject
    {
        return CastingProject::create($attrs + [
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Proyek '.uniqid(),
            'deadline' => now()->addWeek(),
            'kuota' => 5,
        ]);
    }

    private function daftar(CastingProject $proyek, string $status): ProjectApplication
    {
        return ProjectApplication::create([
            'casting_project_id' => $proyek->id,
            'extras_id' => $this->profile->id,
            'status_partisipasi' => $status,
        ]);
    }

    public function test_client_yang_boleh_akses_hanya_lihat_proyeknya_sendiri(): void
    {
        $clientA = User::factory()->create(['role' => 'client']);
        $clientB = User::factory()->create(['role' => 'client']);
        $proyekA = $this->proyek(['client_id' => $clientA->id, 'nama_produksi' => 'Produksi Terbuka A']);
        $proyekB = $this->proyek(['client_id' => $clientB->id, 'nama_produksi' => 'Produksi Rahasia B']);
        $this->daftar($proyekA, 'lolos');
        $this->daftar($proyekB, 'lolos');

        $response = $this->actingAs($clientA)->get(route('client.extras.profil', $this->profile->user));

        $response->assertOk()
            ->assertSee('Produksi Terbuka A')
            ->assertSee('Pernah ikut')
            ->assertSee('<strong>2</strong>', false)
            ->assertDontSee('Produksi Rahasia B');
    }

    public function test_status_ditolak_dibatalkan_tidak_tampil_di_mode_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $proyekLolos = $this->proyek(['client_id' => $client->id, 'nama_produksi' => 'Produksi Lolos']);
        $proyekTolak = $this->proyek(['client_id' => $client->id, 'nama_produksi' => 'Produksi Ditolak']);
        $this->daftar($proyekLolos, 'lolos');
        $this->daftar($proyekTolak, 'ditolak');

        $this->actingAs($client)->get(route('client.extras.profil', $this->profile->user))
            ->assertOk()
            ->assertSee('Produksi Lolos')
            ->assertDontSee('Produksi Ditolak')
            ->assertDontSee('Riwayat pendaftaran lain');
    }

    public function test_admin_dikelola_oleh_link_ke_lineup_yang_bukan_link_ke_detail(): void
    {
        $adminPic = User::factory()->create(['role' => 'admin']);
        $adminLain = User::factory()->create(['role' => 'admin']);
        $proyek = $this->proyek(['admin_id' => $adminPic->id, 'nama_produksi' => 'Produksi Lineup']);
        $app = $this->daftar($proyek, 'lolos');

        $this->actingAs($adminPic)->get(route('admin.extras.profil', $this->profile->user))
            ->assertOk()
            ->assertSee(route('admin.projects.applicants', [$proyek, 'tab' => 'client']).'#app-'.$app->id, false);

        $this->actingAs($adminLain)->get(route('admin.extras.profil', $this->profile->user))
            ->assertOk()
            ->assertSee(route('admin.projects.show', $proyek), false)
            ->assertDontSee(route('admin.projects.applicants', [$proyek, 'tab' => 'client']).'#app-'.$app->id, false);
    }

    public function test_status_lain_dilipat_ke_details_untuk_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $proyekLolos = $this->proyek(['admin_id' => $admin->id, 'nama_produksi' => 'Produksi Utama']);
        $proyekDitolak = $this->proyek(['admin_id' => $admin->id, 'nama_produksi' => 'Produksi Ditolak Admin']);
        $this->daftar($proyekLolos, 'lolos');
        $this->daftar($proyekDitolak, 'ditolak');

        $this->actingAs($admin)->get(route('admin.extras.profil', $this->profile->user))
            ->assertOk()
            ->assertSee('Produksi Utama')
            ->assertSee('Riwayat pendaftaran lain (1)')
            ->assertSee('Produksi Ditolak Admin');
    }

    public function test_tidak_n_plus_1_saat_banyak_pendaftaran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 8) as $i) {
            $this->daftar($this->proyek(['admin_id' => $admin->id]), 'lolos');
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.extras.profil', $this->profile->user))->assertOk();
        $jumlahQuery = count(DB::getQueryLog());
        DB::disableQueryLog();

        // dikelolaOleh() tetap 1 query per proyek (gerbang CM, tidak diubah) - batas longgar
        // supaya hanya menangkap N+1 di luar itu (mis. lazy-load castingProject/shootingDates/class).
        $this->assertLessThan(8 * 2 + 15, $jumlahQuery, "Query terlalu banyak: {$jumlahQuery}");
    }
}

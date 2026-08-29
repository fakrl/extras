<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MarginRecapTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('rolePassProvider')]
    public function test_role_diizinkan_200(string $role, string $url): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get($url)->assertOk();
    }

    public static function rolePassProvider(): array
    {
        return [
            ['admin_default', '/admin/rekap-margin'],
            ['super_admin', '/super-admin/rekap-margin'],
            ['admin_default', '/super-admin/rekap-margin'],
            ['super_admin', '/admin/rekap-margin'],
        ];
    }

    #[DataProvider('roleBlockProvider')]
    public function test_sub_role_admin_ditolak_403(string $role, string $url): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get($url)->assertStatus(403);
    }

    public static function roleBlockProvider(): array
    {
        $urls = ['/admin/rekap-margin', '/super-admin/rekap-margin'];
        $roles = ['admin_talco', 'admin_korlap', 'admin_sosmed'];

        $cases = [];
        foreach ($roles as $role) {
            foreach ($urls as $url) {
                $cases["{$role} @ {$url}"] = [$role, $url];
            }
        }

        return $cases;
    }

    public function test_margin_dihitung_benar_per_kelas_bukan_dikali_kuota(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Margin Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $kelasA = $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu',
            'budget_client' => 400000,
            'kuota_kelas' => 5,
        ]);

        $kelasB = $project->classes()->create([
            'nama_kelas' => 'Bapak-bapak',
            'budget_client' => 600000,
            'kuota_kelas' => 5,
        ]);

        $extrasA = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id, 'alias' => 'Extras A']);
        $extrasB = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id, 'alias' => 'Extras B']);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extrasA->id,
            'casting_project_class_id' => $kelasA->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 250000,
        ]);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extrasB->id,
            'casting_project_class_id' => $kelasB->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 300000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/rekap-margin');

        $response->assertOk();
        // kelas A: 400.000 - 250.000 = 150.000 | kelas B: 600.000 - 300.000 = 300.000
        // total fee client = 1.000.000, payout = 550.000, margin = 450.000
        // (BUKAN budget_client x kuota_kelas seperti pendekatan lama)
        $response->assertSee('1.000.000');
        $response->assertSee('550.000');
        $response->assertSee('450.000');
    }

    public function test_aplikasi_tanpa_kelas_tetap_masuk_total_sebagai_belum_terklasifikasi(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Margin Legacy',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 2,
        ]);

        $kelas = $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu',
            'budget_client' => 400000,
            'kuota_kelas' => 2,
        ]);

        $extrasKelas = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id, 'alias' => 'Extras Kelas']);
        $extrasLegacy = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id, 'alias' => 'Extras Legacy']);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extrasKelas->id,
            'casting_project_class_id' => $kelas->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 250000,
        ]);

        // Data lama/belum dimigrasi: casting_project_class_id null. Harus
        // tetap ikut ke total (bukan silently di-drop), lewat baris terpisah.
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extrasLegacy->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/rekap-margin');

        $response->assertOk();
        $response->assertSee('Belum terklasifikasi');
        // fee client = 400.000 (cuma dari kelas), payout = 250.000 + 200.000 = 450.000
        // margin = 400.000 - 450.000 = -50.000 (payout legacy tetap mengurangi
        // margin, tidak menghilang dari total)
        $response->assertSee('450.000');
        $response->assertSee('-50.000');
    }

    public function test_aplikasi_ditolak_tidak_dihitung_sebagai_payout(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Margin Test 2',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 1,
        ]);

        $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu',
            'budget_client' => 400000,
            'kuota_kelas' => 1,
        ]);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Extras Ditolak']);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'ditolak',
            'fee_final' => 300000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/rekap-margin');

        $response->assertOk();
        // payout harus 0 karena status "ditolak" tidak masuk hitungan
        $response->assertSee('Rp 0');
    }
}

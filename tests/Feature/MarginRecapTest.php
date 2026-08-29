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

    public function test_margin_dihitung_benar_dari_budget_client_dan_fee_final(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Margin Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 3,
        ]);

        $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu',
            'budget_client' => 400000,
            'kuota_kelas' => 3,
        ]);

        foreach (range(1, 3) as $i) {
            $extrasUser = User::factory()->create(['role' => 'extras']);
            $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => "Extras {$i}"]);

            ProjectApplication::create([
                'casting_project_id' => $project->id,
                'extras_id' => $extras->id,
                'status_partisipasi' => 'lolos',
                'fee_final' => 250000,
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/rekap-margin');

        $response->assertOk();
        // total fee client = 400000 x 3 = 1.200.000, payout = 250000 x 3 = 750.000
        // margin = 450.000 = 150.000 x 3 heads
        $response->assertSee('1.200.000');
        $response->assertSee('750.000');
        $response->assertSee('450.000');
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

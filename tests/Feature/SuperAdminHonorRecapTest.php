<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-49: rekap honor seluruh Admin di dashboard Super Admin.
 */
class SuperAdminHonorRecapTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
    }

    public function test_total_honor_dihitung_termasuk_addon(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $korlap = User::factory()->create(['role' => 'admin_korlap', 'name' => 'Budi Korlap']);
        $project = $this->buatProyek($korlap);

        $assignment = AdminProjectAssignment::create([
            'casting_project_id' => $project->id, 'user_id' => $korlap->id,
            'assigned_by' => $superAdmin->id, 'status_log' => 'selesai',
        ]);
        $payroll = $assignment->payroll()->create(['nominal_pokok' => 500000]);
        $payroll->addons()->create(['label' => 'Transport', 'nominal' => 50000, 'created_by' => $superAdmin->id]);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Budi Korlap');
        $response->assertSee('Rp 550.000');
    }

    public function test_admin_tanpa_assignment_tetap_muncul_dengan_total_nol(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'admin_talco', 'name' => 'Talco Baru']);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Talco Baru');
        $response->assertSee('Rp 0');
    }

    public function test_proyek_berjalan_tidak_ikut_dihitung_honornya(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin_default', 'name' => 'Admin Aktif']);
        $project = $this->buatProyek($admin);

        AdminProjectAssignment::create([
            'casting_project_id' => $project->id, 'user_id' => $admin->id,
            'assigned_by' => $superAdmin->id, 'status_log' => 'berjalan',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Admin Aktif');
        $response->assertSee('Rp 0');
    }

    public static function bukanSuperAdminProvider(): array
    {
        return [
            ['admin_default'], ['admin_talco'], ['admin_korlap'], ['admin_sosmed'],
            ['casting_director'], ['extras'],
        ];
    }

    #[DataProvider('bukanSuperAdminProvider')]
    public function test_role_selain_super_admin_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('super-admin.dashboard'))->assertForbidden();
    }
}

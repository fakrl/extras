<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * BD.3.2: honor staf belum dibayar muncul di "Perlu tindakan" dashboard Super Admin.
 */
class SuperAdminHonorRecapTest extends TestCase
{
    use RefreshDatabase;

    private function buatPayroll(User $superAdmin, string $statusBayar = 'belum'): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Budi Korlap']);
        $project = CastingProject::create([
            'admin_id' => $korlap->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
            'client_id' => User::factory()->create(['role' => 'client'])->id,
        ]);
        $assignment = AdminProjectAssignment::create([
            'casting_project_id' => $project->id, 'user_id' => $korlap->id,
            'assigned_by' => $superAdmin->id, 'status_log' => 'selesai',
        ]);
        $payroll = $assignment->payroll()->create(['nominal_pokok' => 500000, 'status_bayar' => $statusBayar]);
        $payroll->addons()->create(['label' => 'Transport', 'nominal' => 50000, 'created_by' => $superAdmin->id]);
    }

    public function test_honor_staf_belum_dibayar_termasuk_addon_muncul_di_perlu_tindakan(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->buatPayroll($superAdmin);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Honor staf belum dibayar');
        $response->assertSee('Rp 550.000');
        $response->assertSee(route('admin.projects.index', ['bayar' => 'staf']), false);
        // Bug HP Erlin (1 Sep 2026): tanpa `color-scheme`, browser mobile bisa
        // paksa dark-mode sendiri walau toggle app bilang light - lihat
        // partials/theme-style.blade.php & layouts/app.blade.php.
        $response->assertSee('color-scheme', false);
    }

    public function test_honor_staf_sudah_dibayar_tidak_muncul_dan_empty_state_tampil(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->buatPayroll($superAdmin, 'sudah');

        $this->actingAs($superAdmin)->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Honor staf belum dibayar')
            ->assertSee('Semua aman')->assertDontSee('dashboard-grid-2col sa-top-grid');
    }

    public static function bukanSuperAdminProvider(): array
    {
        return [
            ['admin'], ['korlap'], ['client'], ['extras'],
        ];
    }

    #[DataProvider('bukanSuperAdminProvider')]
    public function test_role_lain_tidak_bisa_akses_dashboard_super_admin(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('super-admin.dashboard'))->assertForbidden();
    }
}

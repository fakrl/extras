<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CR.2 (26 Okt 2026): kartu "Total saat ini" (honor staf) dikeluarkan dari
 * dashboard SA - invoice jadi KPI, honor staf tetap ada di halaman payroll/
 * riwayat kerja masing-masing (tidak dihapus dari aplikasi, cuma dari dashboard).
 */
class SuperAdminHonorRecapTest extends TestCase
{
    use RefreshDatabase;

    private function buatPayroll(User $superAdmin, string $statusBayar = 'belum'): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Budi Korlap']);
        $project = CastingProject::create([
            'admin_id' => $korlap->id, 'nama_produksi' => 'P',
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

    public function test_honor_staf_tidak_lagi_tampil_di_dashboard(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->buatPayroll($superAdmin);

        $this->actingAs($superAdmin)->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Honor staf belum dibayar')
            ->assertDontSee('Rp 550.000')
            ->assertDontSee('Total saat ini')
            // Bug HP Erlin (1 Sep 2026): tanpa `color-scheme`, browser mobile bisa
            // paksa dark-mode sendiri walau toggle app bilang light.
            ->assertSee('color-scheme', false);
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

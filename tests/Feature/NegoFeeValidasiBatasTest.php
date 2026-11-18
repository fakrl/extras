<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cek validasi nominal nego fee (counter-offer) yang belum pernah dites:
 * 0/negatif/non-numeric harus ditolak. Juga dokumentasikan bahwa TIDAK ADA
 * batas atas (max) nominal - ini gap yang sengaja tidak diperbaiki di sini
 * karena "berapa batas wajar fee" adalah keputusan bisnis, bukan gap teknis
 * yang jelas (tidak ada angka plafon di dokumen manapun).
 */
class NegoFeeValidasiBatasTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(string $status = 'direview_admin'): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Nego Batas',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ]);
    }

    public static function nominalInvalid(): array
    {
        return [
            'nol' => [0],
            'negatif' => [-50000],
            'non-numeric' => ['sejuta'],
        ];
    }

    #[DataProvider('nominalInvalid')]
    public function test_admin_ajukan_fee_awal_tolak_nominal_invalid(mixed $nominal): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => $nominal])
            ->assertSessionHasErrors('nominal');
        $this->assertSame(0, $application->feeNegotiations()->count());
    }

    #[DataProvider('nominalInvalid')]
    public function test_admin_counter_tolak_nominal_invalid(mixed $nominal): void
    {
        $application = $this->buatAplikasi('nego_fee');
        $admin = $application->castingProject->admin;
        $application->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'extras', 'nominal' => 300000, 'aksi' => 'tawar']);

        $this->actingAs($admin)->post(route('admin.negotiations.counter', $application), ['nominal' => $nominal])
            ->assertSessionHasErrors('nominal');
    }

    #[DataProvider('nominalInvalid')]
    public function test_extras_counter_tolak_nominal_invalid(mixed $nominal): void
    {
        $application = $this->buatAplikasi('nego_fee');
        $extrasUser = $application->extras->user;
        $application->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 300000, 'aksi' => 'tawar']);

        $this->actingAs($extrasUser)->post(route('extras.negotiations.counter', $application), ['nominal' => $nominal])
            ->assertSessionHasErrors('nominal');
    }

    /**
     * Tidak ada rule 'max:' pada nominal nego fee di manapun (Admin\FeeNegotiationController
     * maupun Extras\FeeNegotiationController). Tes ini SENGAJA mengunci perilaku saat ini
     * (nominal sangat besar DITERIMA) supaya kalau nanti ada plafon, perubahannya jelas
     * disengaja - bukan laporan bug yang diperbaiki diam-diam di sini.
     */
    public function test_nominal_sangat_besar_saat_ini_masih_diterima_tanpa_batas_atas(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 9999999999])
            ->assertSessionDoesntHaveErrors('nominal');
        $this->assertSame(9999999999.0, (float) $application->feeNegotiations()->first()->nominal);
    }
}

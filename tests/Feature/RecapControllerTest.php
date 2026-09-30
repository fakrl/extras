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
 * RF-51: rekap Extras paling sering terpilih + rekap status. Perluasan:
 * rekap Extras paling sering membatalkan mendadak (docs/CLAUDE.md §9,
 * sebelumnya cuma ada agregat status, belum ada ranking per-individu).
 */
class RecapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_extras_paling_sering_terpilih_terurut_benar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $seringDipilih = ExtrasProfile::factory()->for(
            User::factory()->state(['role' => 'extras', 'username' => 'sering_dipilih'])
        )->create();
        ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $seringDipilih->id,
            'status_partisipasi' => 'lolos',
        ]);

        $jarangDipilih = ExtrasProfile::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.recap.index'));

        $response->assertOk();
        $response->assertSee('sering_dipilih');
        $response->assertViewHas('extrasPalingSering', function ($list) use ($seringDipilih) {
            return $list->first()->id === $seringDipilih->id && $list->first()->applications_count === 1;
        });
    }

    public function test_rekap_sering_batal_hanya_menampilkan_yang_pernah_batal_terurut_desc(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $seringBatal = ExtrasProfile::factory()->for(
            User::factory()->state(['role' => 'extras', 'username' => 'tukang_batal'])
        )->create();
        $sesekaliBatal = ExtrasProfile::factory()->for(
            User::factory()->state(['role' => 'extras', 'username' => 'sesekali_batal'])
        )->create();
        $tidakPernahBatal = ExtrasProfile::factory()->create();
        $batal = fn (ExtrasProfile $e, bool $mendadak = true, string $oleh = 'extras') => ProjectApplication::create([
            'casting_project_id' => CastingProject::factory()->create()->id, 'extras_id' => $e->id, 'status_partisipasi' => 'dibatalkan',
        ])->cancellations()->create(['dibatalkan_oleh' => $oleh, 'alasan' => 'x', 'is_mendadak' => $mendadak]);
        foreach (range(1, 3) as $_) {
            $batal($seringBatal);
        }
        $batal($sesekaliBatal);
        $batal($sesekaliBatal, false);
        $batal($tidakPernahBatal, true, 'admin');

        $response = $this->actingAs($admin)->get(route('admin.recap.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['tukang_batal', 'sesekali_batal']);
        $response->assertViewHas('extrasSeringBatal', function ($list) use ($seringBatal, $sesekaliBatal) {
            return $list->pluck('id')->all() === [$seringBatal->id, $sesekaliBatal->id];
        });
    }

    public function test_halaman_recap_tetap_ok_kalau_belum_ada_yang_pernah_batal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ExtrasProfile::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.recap.index'));

        $response->assertOk();
        $response->assertSee('Belum ada pembatalan tercatat.');
    }

    public static function bukanAdminDefaultProvider(): array
    {
        return [
            ['korlap'], ['client'], ['extras'],
        ];
    }

    #[DataProvider('bukanAdminDefaultProvider')]
    public function test_role_selain_admin_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('admin.recap.index'))->assertForbidden();
    }
}

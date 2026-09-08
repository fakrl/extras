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
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $seringDipilih = ExtrasProfile::factory()->create(['alias' => 'Sering Dipilih']);
        ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $seringDipilih->id,
            'status_partisipasi' => 'lolos',
        ]);

        $jarangDipilih = ExtrasProfile::factory()->create(['alias' => 'Belum Pernah']);

        $response = $this->actingAs($admin)->get(route('admin.recap.index'));

        $response->assertOk();
        $response->assertSee('Sering Dipilih');
        $response->assertViewHas('extrasPalingSering', function ($list) use ($seringDipilih) {
            return $list->first()->id === $seringDipilih->id && $list->first()->applications_count === 1;
        });
    }

    public function test_rekap_sering_batal_hanya_menampilkan_yang_pernah_batal_terurut_desc(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $seringBatal = ExtrasProfile::factory()->create(['alias' => 'Tukang Batal', 'cancel_count' => 3]);
        $sesekaliBatal = ExtrasProfile::factory()->create(['alias' => 'Sesekali Batal', 'cancel_count' => 1]);
        $tidakPernahBatal = ExtrasProfile::factory()->create(['alias' => 'Rajin', 'cancel_count' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.recap.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Tukang Batal', 'Sesekali Batal']);
        $response->assertViewHas('extrasSeringBatal', function ($list) use ($seringBatal, $sesekaliBatal) {
            return $list->pluck('id')->all() === [$seringBatal->id, $sesekaliBatal->id];
        });
    }

    public function test_halaman_recap_tetap_ok_kalau_belum_ada_yang_pernah_batal(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        ExtrasProfile::factory()->create(['cancel_count' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.recap.index'));

        $response->assertOk();
        $response->assertSee('Belum ada pembatalan tercatat.');
    }

    public static function bukanAdminDefaultProvider(): array
    {
        return [
            ['admin_talco'], ['admin_korlap'], ['admin_sosmed'],
            ['casting_director'], ['extras'], ['super_admin'],
        ];
    }

    #[DataProvider('bukanAdminDefaultProvider')]
    public function test_role_selain_admin_default_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('admin.recap.index'))->assertForbidden();
    }
}

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
 * RF-54: badge Apresiasi Extras, murni catatan internal Admin Default —
 * tidak pernah boleh terlihat oleh CD maupun Extras sendiri.
 */
class ApresiasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $extras = ExtrasProfile::factory()->create(['alias' => 'Alias Rajin']);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan_ke_cd',
        ]);
    }

    public function test_admin_default_bisa_memberi_apresiasi_dengan_catatan(): void
    {
        $application = $this->buatAplikasi();
        $admin = User::where('role', 'admin_default')->first();

        $response = $this->actingAs($admin)->post(route('admin.applications.apresiasi', $application), [
            'apresiasi' => '1',
            'apresiasi_catatan' => 'Selalu tepat waktu dan profesional.',
        ]);

        $response->assertRedirect();
        $application->extras->refresh();
        $this->assertTrue($application->extras->apresiasi);
        $this->assertSame('Selalu tepat waktu dan profesional.', $application->extras->apresiasi_catatan);
    }

    public function test_mencabut_apresiasi_menghapus_catatan(): void
    {
        $application = $this->buatAplikasi();
        $application->extras->update(['apresiasi' => true, 'apresiasi_catatan' => 'Bagus.']);
        $admin = User::where('role', 'admin_default')->first();

        $this->actingAs($admin)->post(route('admin.applications.apresiasi', $application), [
            'apresiasi' => '0',
        ]);

        $application->extras->refresh();
        $this->assertFalse($application->extras->apresiasi);
        $this->assertNull($application->extras->apresiasi_catatan);
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
        $application = $this->buatAplikasi();
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->post(route('admin.applications.apresiasi', $application), ['apresiasi' => '1'])
            ->assertForbidden();
    }

    public function test_apresiasi_tidak_muncul_di_halaman_review_cd(): void
    {
        $application = $this->buatAplikasi();
        $application->extras->update(['apresiasi' => true, 'apresiasi_catatan' => 'Rahasia internal admin.']);

        $cd = User::factory()->create(['role' => 'casting_director']);
        $application->castingProject->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $response = $this->actingAs($cd)->get(route('cd.reviews.index'));

        $response->assertOk();
        $response->assertDontSee('Apresiasi');
        $response->assertDontSee('Rahasia internal admin.');
    }

    public function test_apresiasi_tidak_muncul_di_halaman_profil_extras_sendiri(): void
    {
        $application = $this->buatAplikasi();
        $application->extras->update(['apresiasi' => true, 'apresiasi_catatan' => 'Rahasia internal admin.']);
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)->get(route('extras.profile.show'));

        $response->assertOk();
        $response->assertDontSee('Apresiasi');
        $response->assertDontSee('Rahasia internal admin.');
    }
}

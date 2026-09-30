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
 * RF-54: badge Apresiasi Extras, murni catatan internal Admin Default -
 * tidak pernah boleh terlihat oleh Client maupun Extras sendiri.
 */
class ApresiasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $extras = ExtrasProfile::factory()->create();

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan_ke_client',
        ]);
    }

    public function test_admin_bisa_memberi_apresiasi_dengan_catatan(): void
    {
        $application = $this->buatAplikasi();
        $admin = User::where('role', 'admin')->first();

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
        $admin = User::where('role', 'admin')->first();

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
            ['korlap'], ['client'], ['extras'],
        ];
    }

    #[DataProvider('bukanAdminDefaultProvider')]
    public function test_role_selain_admin_ditolak(string $role): void
    {
        $application = $this->buatAplikasi();
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->post(route('admin.applications.apresiasi', $application), ['apresiasi' => '1'])
            ->assertForbidden();
    }

    public function test_apresiasi_tidak_muncul_di_halaman_review_client(): void
    {
        $application = $this->buatAplikasi();
        $application->extras->update(['apresiasi' => true, 'apresiasi_catatan' => 'Rahasia internal admin.']);

        $klien = User::factory()->create(['role' => 'client']);
        $application->castingProject->update(['client_id' => $klien->id]);

        $response = $this->actingAs($klien)->get(route('client.reviews.index'));

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

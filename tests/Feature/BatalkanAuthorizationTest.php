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
 * Gap coverage (DEV-NOTES review, 30 Agu 2026): batalkan() sebelumnya cuma
 * ditest lewat pemanggilan method model langsung (ProjectApplicationTest).
 * Tes ini lewat HTTP request beneran ke 2 route-nya, supaya otorisasi
 * middleware (`role:`) dan controller (`pastikanMilikSendiri()`) ikut
 * teruji, bukan cuma logic model.
 */
class BatalkanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function buatApplicationDeal(): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);
    }

    public function test_extras_lain_hit_batalkan_via_http_dapat_403(): void
    {
        $application = $this->buatApplicationDeal();

        $extrasLainUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasLainUser->id, 'alias' => 'Alias Lain']);

        $response = $this->actingAs($extrasLainUser)
            ->post(route('extras.negotiations.batalkan', $application), ['alasan' => 'Bukan punya saya']);

        $response->assertStatus(403);
        $this->assertSame('deal', $application->fresh()->status_partisipasi);
    }

    public function test_extras_pemilik_hit_batalkan_via_http_berhasil(): void
    {
        $application = $this->buatApplicationDeal();
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)
            ->post(route('extras.negotiations.batalkan', $application), ['alasan' => 'Berubah pikiran']);

        $response->assertRedirect();
        $this->assertSame('dibatalkan', $application->fresh()->status_partisipasi);
    }

    #[DataProvider('subRoleAdminProvider')]
    public function test_sub_role_admin_hit_batalkan_via_http_dapat_403(string $role): void
    {
        $application = $this->buatApplicationDeal();
        $subRoleAdmin = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($subRoleAdmin)
            ->post(route('admin.negotiations.batalkan', $application), ['alasan' => 'Bukan wewenang saya']);

        $response->assertStatus(403);
        $this->assertSame('deal', $application->fresh()->status_partisipasi);
    }

    public static function subRoleAdminProvider(): array
    {
        return [
            'admin_talco' => ['admin_talco'],
            'admin_korlap' => ['admin_korlap'],
            'admin_sosmed' => ['admin_sosmed'],
        ];
    }

    public function test_admin_default_hit_batalkan_via_http_berhasil(): void
    {
        $application = $this->buatApplicationDeal();
        $adminDefault = User::factory()->create(['role' => 'admin_default']);

        $response = $this->actingAs($adminDefault)
            ->post(route('admin.negotiations.batalkan', $application), ['alasan' => 'Client reschedule']);

        $response->assertRedirect();
        $this->assertSame('dibatalkan', $application->fresh()->status_partisipasi);
    }
}

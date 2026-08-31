<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentStatusGateTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(User $admin, ExtrasProfile $extras, string $status): ProjectApplication
    {
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
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    public static function statusBelumLolosProvider(): array
    {
        return [
            ['diajukan'],
            ['nego_fee'],
            ['deal'],
        ];
    }

    public static function statusLolosKeAtasProvider(): array
    {
        return [
            ['lolos'],
            ['kontrak_ditandatangani'],
            ['selesai_produksi'],
        ];
    }

    #[DataProvider('statusBelumLolosProvider')]
    public function test_show_ditolak_untuk_status_belum_lolos(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);

        $this->actingAs($admin)->get(route('payments.show', $application))->assertStatus(422);
    }

    #[DataProvider('statusBelumLolosProvider')]
    public function test_transfer_ditolak_untuk_status_belum_lolos(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);
        $application->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($admin)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 100),
        ])->assertStatus(422);
    }

    #[DataProvider('statusBelumLolosProvider')]
    public function test_konfirmasi_ditolak_untuk_status_belum_lolos(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);
        $application->payment()->create(['status' => 'ditransfer']);

        $this->actingAs($extrasUser)->post(route('payments.confirm', $application))->assertStatus(422);
    }

    #[DataProvider('statusBelumLolosProvider')]
    public function test_addon_ditolak_untuk_status_belum_lolos(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);
        $application->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($admin)->post(route('payments.addon', $application), [
            'label' => 'Reimburse transport',
            'nominal' => 50000,
        ])->assertStatus(422);
    }

    #[DataProvider('statusLolosKeAtasProvider')]
    public function test_show_normal_untuk_status_lolos_ke_atas(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);

        $this->actingAs($admin)->get(route('payments.show', $application))->assertOk();
    }

    #[DataProvider('statusLolosKeAtasProvider')]
    public function test_transfer_normal_untuk_status_lolos_ke_atas(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);
        $application->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($admin)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 100),
        ])->assertRedirect();
    }

    #[DataProvider('statusLolosKeAtasProvider')]
    public function test_konfirmasi_normal_untuk_status_lolos_ke_atas(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);
        $application->payment()->create(['status' => 'ditransfer']);

        $this->actingAs($extrasUser)->post(route('payments.confirm', $application))->assertRedirect();
    }

    #[DataProvider('statusLolosKeAtasProvider')]
    public function test_addon_normal_untuk_status_lolos_ke_atas(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, $status);
        $application->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($admin)->post(route('payments.addon', $application), [
            'label' => 'Reimburse transport',
            'nominal' => 50000,
        ])->assertRedirect();
    }
}

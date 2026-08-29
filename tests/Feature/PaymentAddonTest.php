<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAddonTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(User $admin, ExtrasProfile $extras, string $statusPembayaran = 'belum_dibayar'): ProjectApplication
    {
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
            'fee_final' => 200000,
        ]);

        $application->payment()->create(['status' => $statusPembayaran]);

        return $application;
    }

    public function test_extras_bisa_tambah_addon_untuk_aplikasi_sendiri(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras);

        $response = $this->actingAs($extrasUser)->post(route('payments.addon', $application), [
            'label' => 'Reimburse transport',
            'nominal' => 50000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payment_addons', [
            'addable_id' => $application->payment->id,
            'label' => 'Reimburse transport',
            'created_by' => $extrasUser->id,
        ]);
    }

    public function test_extras_tidak_bisa_tambah_addon_aplikasi_extras_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras);

        $extrasLainUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasLainUser->id, 'alias' => 'Alias Lain']);

        $response = $this->actingAs($extrasLainUser)->post(route('payments.addon', $application), [
            'label' => 'Reimburse transport',
            'nominal' => 50000,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_default_tetap_bisa_tambah_addon(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras);

        $response = $this->actingAs($admin)->post(route('payments.addon', $application), [
            'label' => 'Reimburse penginapan',
            'nominal' => 100000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payment_addons', [
            'addable_id' => $application->payment->id,
            'label' => 'Reimburse penginapan',
            'created_by' => $admin->id,
        ]);
    }

    public function test_addon_ditolak_untuk_admin_setelah_dikonfirmasi_diterima(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, 'dikonfirmasi_diterima');

        $response = $this->actingAs($admin)->post(route('payments.addon', $application), [
            'label' => 'Reimburse transport',
            'nominal' => 50000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('payment_addons', ['addable_id' => $application->payment->id]);
    }

    public function test_addon_ditolak_untuk_extras_setelah_dikonfirmasi_diterima(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $application = $this->buatAplikasi($admin, $extras, 'dikonfirmasi_diterima');

        $response = $this->actingAs($extrasUser)->post(route('payments.addon', $application), [
            'label' => 'Reimburse transport',
            'nominal' => 50000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('payment_addons', ['addable_id' => $application->payment->id]);
    }
}

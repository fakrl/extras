<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SPEC AY.4.6: rekening + total untuk Admin, bukti transfer untuk Extras pemilik saja.
 */
class AyAksiAmanTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(User $admin, ExtrasProfile $extras): ProjectApplication
    {
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek AY4',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
            'fee_final' => 200000,
        ]);
    }

    public function test_admin_lihat_rekening_dan_total_sebelum_transfer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'rekening' => 'BCA 1234567890']);
        $application = $this->buatAplikasi($admin, $extras);
        $application->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($admin)->get(route('payments.show', $application))
            ->assertOk()
            ->assertSee('BCA 1234567890')
            ->assertSee('Rp 200.000');
    }

    public function test_bukti_transfer_hanya_untuk_pemilik_dan_admin(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payments/bukti-transfer/a.jpg', 'x');

        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras);
        $application->payment()->create([
            'status' => 'ditransfer',
            'bukti_transfer_path' => 'payments/bukti-transfer/a.jpg',
        ]);

        $lain = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $lain->id]);

        $this->actingAs($extrasUser)->get(route('payments.bukti', $application))->assertOk();
        $this->actingAs($admin)->get(route('payments.bukti', $application))->assertOk();
        $this->actingAs($lain)->get(route('payments.bukti', $application))->assertForbidden();
    }
}

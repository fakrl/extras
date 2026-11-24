<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** DA.6: akun Extras hasil alur admin-create (bukan registrasi mandiri) tetap nyambung ke alur lengkapi-ktp existing tanpa perlakuan khusus. */
class ExtrasManualKtpFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_extras_buatan_admin_yang_lolos_tetap_bisa_lengkapi_ktp_dan_siap_kontrak(): void
    {
        Mail::fake();
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.akun.extras.store'), [
            'name' => 'Extras Manual',
            'username' => 'extras_manual',
            'nomor_wa' => '0812-0000-1111',
            'email' => 'extras-manual@example.com',
        ])->assertRedirect();

        $extrasUser = User::where('username', 'extras_manual')->first();
        $this->assertNotNull($extrasUser);
        $this->assertEmpty($extrasUser->extrasProfile->nik_hash);

        // Isolasi concern: password & consent sudah dites terpisah (DA.2/DA.5),
        // di sini fokus verifikasi sambungan ke alur lengkapi-ktp existing.
        $extrasUser->forceFill(['wajib_ganti_password' => false, 'privasi_disetujui_at' => now()])->save();

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Manual Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extrasUser->extrasProfile->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);

        $this->actingAs($extrasUser)
            ->get(route('extras.kontrak.lengkapi-ktp', $application))
            ->assertOk();

        $response = $this->actingAs($extrasUser)
            ->post(route('extras.kontrak.simpan-ktp', $application), [
                'nik' => '3201234567890099',
                'rekening' => 'BCA 123456',
            ]);

        $response->assertRedirect(route('contracts.show', $application));

        $profile = $extrasUser->extrasProfile->fresh();
        $this->assertNotNull($profile->nik);
        $this->assertNotEmpty($profile->nik_hash);
    }
}

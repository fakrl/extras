<?php

namespace Tests\Feature;

use App\Exceptions\NikDuplikatException;
use App\Models\CastingProject;
use App\Models\Contract;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LengkapiKtpTest extends TestCase
{
    use RefreshDatabase;

    private function buatApplicationLolos(): ProjectApplication
    {
        return $this->buatApplication('lolos');
    }

    private function buatApplication(string $status): ProjectApplication
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
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    public function test_nik_valid_tersimpan_dan_kontrak_bisa_digenerate(): void
    {
        Mail::fake();
        Storage::fake('local');

        $application = $this->buatApplicationLolos();
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)
            ->post(route('extras.kontrak.simpan-ktp', $application), [
                'nik' => '3201234567890001',
                'rekening' => '',
            ]);

        $response->assertRedirect(route('contracts.show', $application));

        $extras = $application->extras->fresh();
        $this->assertNotNull($extras->nik);
        $this->assertSame('3201234567890001', $extras->nik);
        $this->assertNotNull($extras->nik_hash);

        $kontrakResponse = $this->actingAs($extrasUser)->get(route('contracts.show', $application));
        $kontrakResponse->assertOk();
        $this->assertDatabaseCount('contracts', 1);
    }

    /**
     * Gap coverage (review DEV-NOTES): mutator nik() strip semua karakter
     * non-digit (preg_replace) sebelum di-hash, jadi 1 NIK yang sama
     * ditulis dengan format pemisah beda (polos/strip/titik) HARUS
     * menghasilkan nik_hash IDENTIK — bukan cuma dites dengan 2 nilai
     * yang berbeda.
     */
    public function test_nik_format_berbeda_untuk_digit_sama_hasilkan_hash_identik(): void
    {
        // Tidak disimpan ke DB (nik_hash UNIQUE) — cukup baca hasil mutator
        // in-memory untuk 3 model terpisah yang tidak pernah di-save bareng.
        $polos = new ExtrasProfile;
        $polos->nik = '3201234567890055';

        $strip = new ExtrasProfile;
        $strip->nik = '3201-2345-6789-0055';

        $titik = new ExtrasProfile;
        $titik->nik = '3201.2345.6789.0055';

        $this->assertNotNull($polos->nik_hash);
        $this->assertSame($polos->nik_hash, $strip->nik_hash);
        $this->assertSame($polos->nik_hash, $titik->nik_hash);
    }

    public function test_nik_format_berbeda_dari_nik_terpakai_tetap_terdeteksi_duplikat(): void
    {
        $applicationLain = $this->buatApplicationLolos();
        $applicationLain->extras->lengkapiKtp('3201234567890066', 'BCA 111');

        $application = $this->buatApplicationLolos();

        // Digit sama persis dengan yang sudah dipakai di atas, format beda
        // (strip pemisah) — lengkapiKtp() model harus tetap mendeteksi
        // duplikat lewat nik_hash yang identik, bukan cuma saat format
        // input persis sama string-nya.
        $this->expectException(NikDuplikatException::class);

        $application->extras->lengkapiKtp('3201-2345-6789-0066', 'BCA 222');
    }

    public function test_nik_duplikat_ditolak_dan_tidak_ada_perubahan(): void
    {
        $applicationLain = $this->buatApplicationLolos();
        $applicationLain->extras->lengkapiKtp('3201234567890002', 'BCA 111');

        $application = $this->buatApplicationLolos();
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)
            ->post(route('extras.kontrak.simpan-ktp', $application), [
                'nik' => '3201234567890002',
                'rekening' => 'BCA 222',
            ]);

        $response->assertSessionHas('error');

        $extras = $application->extras->fresh();
        $this->assertNull($extras->nik);
        $this->assertNull($extras->nik_hash);
        $this->assertNull($extras->rekening);
    }

    public function test_nik_bukan_16_digit_ditolak_validasi(): void
    {
        $application = $this->buatApplicationLolos();
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)
            ->post(route('extras.kontrak.simpan-ktp', $application), [
                'nik' => '12345ABCDE',
            ]);

        $response->assertSessionHasErrors('nik');
        $this->assertNull($application->extras->fresh()->nik_hash);
    }

    public function test_akses_kontrak_sebelum_lengkapi_ktp_redirect_dan_kontrak_tidak_dibuat(): void
    {
        $application = $this->buatApplicationLolos();
        $extrasUser = $application->extras->user;

        $countSebelum = Contract::count();

        $response = $this->actingAs($extrasUser)->get(route('contracts.show', $application));

        $response->assertRedirect(route('extras.kontrak.lengkapi-ktp', $application));
        $this->assertSame($countSebelum, Contract::count());
    }

    public function test_extras_dengan_nik_terisi_langsung_ke_halaman_kontrak(): void
    {
        Mail::fake();
        Storage::fake('local');

        $application = $this->buatApplicationLolos();
        $application->extras->lengkapiKtp('3201234567890003', 'BCA 333');
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)->get(route('contracts.show', $application));

        $response->assertOk();
        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_form_ktp_ditolak_403_kalau_status_belum_lolos(): void
    {
        $application = $this->buatApplication('diajukan');
        $extrasUser = $application->extras->user;

        $this->actingAs($extrasUser)
            ->get(route('extras.kontrak.lengkapi-ktp', $application))
            ->assertForbidden();
    }

    public function test_simpan_ktp_ditolak_403_kalau_status_ditolak(): void
    {
        $application = $this->buatApplication('ditolak');
        $extrasUser = $application->extras->user;

        $this->actingAs($extrasUser)
            ->post(route('extras.kontrak.simpan-ktp', $application), [
                'nik' => '3201234567890099',
                'rekening' => '',
            ])
            ->assertForbidden();
    }

    public function test_simpan_ktp_kena_rate_limit_pada_percobaan_keenam(): void
    {
        $application = $this->buatApplicationLolos();
        $extrasUser = $application->extras->user;

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($extrasUser)
                ->post(route('extras.kontrak.simpan-ktp', $application), ['nik' => '12345ABCDE']);
        }

        $response = $this->actingAs($extrasUser)
            ->post(route('extras.kontrak.simpan-ktp', $application), ['nik' => '12345ABCDE']);

        $response->assertStatus(429);
    }
}

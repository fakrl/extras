<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ExtractsPdfText;
use Tests\TestCase;

/** SPEC.md Bagian CY: klausul kuasa & potongan jasa agensi 25% di kontrak, dan centang persetujuan Extras sebelum TTD. */
class CyKlausulKuasaPotonganTest extends TestCase
{
    use ExtractsPdfText, RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function aplikasiLolos(User $admin): ProjectApplication
    {
        $project = CastingProject::create(['admin_id' => $admin->id, 'nama_produksi' => 'Proyek Kuasa', 'deadline' => now()->addDays(7), 'kuota' => 5]);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'nama_asli' => 'Dimas R', 'nik' => '3170000000000002']);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 375000,
        ]);
    }

    public function test_pdf_kontrak_memuat_klausul_kuasa_tanpa_angka_hari(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $application->contract()->create([]);

        $response = $this->actingAs($application->extras->user)->get(route('contracts.download-pdf', $application));
        $response->assertOk();

        $teks = $this->teksPdf($response->streamedContent());
        $this->assertStringContainsString('Kuasa dan Imbalan Jasa Agensi', $teks);
        $this->assertStringContainsString('25%', $teks);
        $this->assertStringContainsString('375.000', $teks);
        $this->assertStringNotContainsString('hari kerja', $teks);
        $this->assertStringNotContainsString('7 hari', $teks);
    }

    public function test_cache_pdf_kontrak_belum_ttd_diregenerasi_dengan_klausul_baru(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $contract = $application->contract()->create([]);
        $path = "contracts/pdf/{$application->id}.pdf";
        Storage::disk('local')->put($path, 'PDF LAMA TANPA KLAUSUL CY');
        $contract->update(['pdf_path' => $path]);

        (require base_path('database/migrations/2026_11_05_000001_hapus_cache_pdf_kontrak_klausul_cy.php'))->up();

        Storage::disk('local')->assertMissing($path);

        $response = $this->actingAs($application->extras->user)->get(route('contracts.download-pdf', $application));
        $response->assertOk();
        $teks = $this->teksPdf($response->streamedContent());
        $this->assertStringContainsString('Kuasa dan Imbalan Jasa Agensi', $teks);
    }

    public function test_pdf_kontrak_sudah_ditandatangani_tidak_disentuh_migrasi_klausul_cy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $contract = $application->contract()->create([]);
        $path = "contracts/pdf/{$application->id}.pdf";
        $isiLama = 'PDF LAMA SUDAH SIGNED TANPA KLAUSUL CY';
        Storage::disk('local')->put($path, $isiLama);
        $contract->update(['pdf_path' => $path, 'signed_at' => now()]);

        (require base_path('database/migrations/2026_11_05_000001_hapus_cache_pdf_kontrak_klausul_cy.php'))->up();

        Storage::disk('local')->assertExists($path);
        $this->assertSame($isiLama, Storage::disk('local')->get($path));
    }

    public function test_halaman_kontrak_menampilkan_klausul_kuasa_sebelum_ttd(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $application->contract()->create([]);

        $response = $this->actingAs($application->extras->user)->get(route('contracts.show', $application));

        $response->assertOk();
        $response->assertSee('Kuasa dan Imbalan Jasa Agensi');
        $response->assertSee('25%');
        $response->assertSee('375.000');
    }

    public function test_extras_tanda_tangan_ditolak_tanpa_centang_kuasa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $application->contract()->create([]);

        $response = $this->actingAs($application->extras->user)
            ->post(route('contracts.sign', $application), ['signature' => self::SIGNATURE]);

        $response->assertSessionHasErrors('setuju_kuasa_potongan');
        $this->assertNull($application->contract->fresh()->ttd_extras_signature_path);
    }

    public function test_extras_tanda_tangan_berhasil_dengan_centang_kuasa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $application->contract()->create([]);

        $response = $this->actingAs($application->extras->user)
            ->post(route('contracts.sign', $application), [
                'signature' => self::SIGNATURE,
                'setuju_kuasa_potongan' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertNotNull($application->contract->fresh()->ttd_extras_signature_path);
    }

    public function test_admin_tanda_tangan_berhasil_tanpa_field_centang_kuasa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->aplikasiLolos($admin);
        $application->contract()->create([]);

        $response = $this->actingAs($admin)
            ->post(route('contracts.sign', $application), ['signature' => self::SIGNATURE]);

        $response->assertSessionHasNoErrors();
        $this->assertNotNull($application->contract->fresh()->ttd_admin_signature_path);
    }
}

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

/** Audit item 3: Grade internal-admin tidak boleh ada di PDF kontrak Extras. */
class ContractPdfGradeTest extends TestCase
{
    use ExtractsPdfText, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function aplikasiLolos(): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create(['admin_id' => $admin->id, 'nama_produksi' => 'Proyek Kontrak', 'deadline' => now()->addDays(7), 'kuota' => 5]);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'nama_asli' => 'Dimas R', 'nik' => '3170000000000001']);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
            'grade' => 'A',
        ]);
    }

    public function test_pdf_kontrak_tidak_memuat_grade(): void
    {
        $application = $this->aplikasiLolos();
        $application->contract()->create([]);
        $extrasUser = $application->extras->user;

        $response = $this->actingAs($extrasUser)->get(route('contracts.download-pdf', $application));
        $response->assertOk();

        $teks = $this->teksPdf($response->streamedContent());
        $this->assertStringNotContainsString('Grade', $teks);
    }

    public function test_cache_pdf_kontrak_belum_ttd_diregenerasi_tanpa_grade(): void
    {
        $application = $this->aplikasiLolos();
        $contract = $application->contract()->create([]);
        $path = "contracts/pdf/{$application->id}.pdf";
        Storage::disk('local')->put($path, 'PDF LAMA DUMMY BERISI Grade');
        $contract->update(['pdf_path' => $path]);

        (require base_path('database/migrations/2026_11_02_000001_hapus_cache_pdf_kontrak_belum_ttd.php'))->up();

        Storage::disk('local')->assertMissing($path);

        $extrasUser = $application->extras->user;
        $response = $this->actingAs($extrasUser)->get(route('contracts.download-pdf', $application));
        $response->assertOk();
        $teks = $this->teksPdf($response->streamedContent());
        $this->assertStringNotContainsString('Grade', $teks);
    }

    public function test_pdf_kontrak_sudah_ditandatangani_tidak_disentuh_migrasi(): void
    {
        $application = $this->aplikasiLolos();
        $contract = $application->contract()->create([]);
        $path = "contracts/pdf/{$application->id}.pdf";
        $isiLama = 'PDF LAMA SUDAH SIGNED BERISI Grade';
        Storage::disk('local')->put($path, $isiLama);
        $contract->update(['pdf_path' => $path, 'signed_at' => now()]);

        (require base_path('database/migrations/2026_11_02_000001_hapus_cache_pdf_kontrak_belum_ttd.php'))->up();

        Storage::disk('local')->assertExists($path);
        $this->assertSame($isiLama, Storage::disk('local')->get($path));
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Audit item 2: Korlap (role asli atau SA sa_mode=korlap) tidak boleh
 * melakukan aksi admin finansial/kontrak/lampiran - itu wewenang
 * Admin PIC/ditugaskan atau SA dalam mode Monitoring Admin.
 */
class KorlapTidakBolehAksiAdminTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function proyekDenganKorlap(User $pic, User $korlap): CastingProject
    {
        $project = CastingProject::create([
            'admin_id' => $pic->id,
            'nama_produksi' => 'Proyek Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
        $project->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $pic->id, 'status_log' => 'berjalan']);

        return $project;
    }

    private function aplikasi(CastingProject $project, string $status = 'lolos'): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    private function sebagaiKorlap(User $sa, User $korlap): self
    {
        return $this->actingAs($sa)->withSession(['sa_mode' => 'korlap', 'sa_view_user_id' => $korlap->id]);
    }

    public function test_korlap_tidak_bisa_tandai_transfer_tambah_addon_atau_selesaikan_sengketa(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->proyekDenganKorlap($pic, $korlap);
        $application = $this->aplikasi($project);
        $application->payment()->create(['status' => 'disengketakan']);

        $this->actingAs($korlap)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 100),
        ])->assertForbidden();

        $this->actingAs($korlap)->post(route('payments.addon', $application), [
            'label' => 'Transport', 'nominal' => 50000,
        ])->assertForbidden();

        $this->actingAs($korlap)->post(route('payments.selesaikan-sengketa', $application), [
            'catatan' => 'sudah dicek',
        ])->assertForbidden();

        $this->assertDatabaseMissing('payment_addons', ['addable_id' => $application->payment->id]);
        $this->assertSame('disengketakan', $application->payment->fresh()->status);
    }

    public function test_korlap_tidak_bisa_ajukan_sengketa_atas_nama_proyek(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->proyekDenganKorlap($pic, $korlap);
        $application = $this->aplikasi($project);
        $application->payment()->create(['status' => 'ditransfer']);

        $this->actingAs($korlap)->post(route('payments.sengketa', $application), [
            'alasan' => 'atas nama extras',
        ])->assertForbidden();

        $this->assertSame('ditransfer', $application->payment->fresh()->status);
    }

    public function test_extras_pemilik_tetap_bisa_konfirmasi_dan_ajukan_sengketa(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create(['admin_id' => $pic->id, 'nama_produksi' => 'Proyek Extras', 'deadline' => now()->addDays(7), 'kuota' => 5]);
        $application = $this->aplikasi($project);
        $application->payment()->create(['status' => 'ditransfer']);
        $extrasUser = $application->extras->user;

        $this->actingAs($extrasUser)->post(route('payments.sengketa', $application), [
            'alasan' => 'nominal kurang',
        ])->assertRedirect();
        $this->assertSame('disengketakan', $application->payment->fresh()->status);

        $application->payment()->update(['status' => 'ditransfer']);
        $this->actingAs($extrasUser)->post(route('payments.confirm', $application))->assertRedirect();
    }

    public function test_korlap_tidak_bisa_ttd_kontrak_atau_upload_lampiran(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->proyekDenganKorlap($pic, $korlap);
        $application = $this->aplikasi($project);
        $application->contract()->create([]);

        $this->actingAs($korlap)->post(route('contracts.sign', $application), [
            'signature' => 'data:image/png;base64,'.base64_encode('x'),
        ])->assertForbidden();
        $this->assertNull($application->contract->fresh()->ttd_admin_signature_path);

        $this->actingAs($korlap)->post(route('project-attachments.store', $project), [
            'files' => [UploadedFile::fake()->create('x.pdf', 10)],
        ])->assertForbidden();
        $this->assertSame(0, $project->attachments()->count());
    }

    public function test_korlap_tidak_bisa_ttd_atau_upload_dokumen_invoice(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->proyekDenganKorlap($pic, $korlap);

        $this->actingAs($korlap)->post(route('invoices.sign', $project), [
            'signature' => 'data:image/png;base64,'.base64_encode('x'),
        ])->assertForbidden();

        $this->actingAs($korlap)->post(route('invoices.upload-custom', $project), [
            'custom_doc' => UploadedFile::fake()->create('voucher.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_sa_mode_korlap_diblokir_sama_seperti_korlap_asli(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->proyekDenganKorlap($pic, $korlap);
        $application = $this->aplikasi($project);
        $application->payment()->create(['status' => 'belum_dibayar']);
        $application->contract()->create([]);

        $this->sebagaiKorlap($sa, $korlap)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 100),
        ])->assertForbidden();

        $this->sebagaiKorlap($sa, $korlap)->post(route('contracts.sign', $application), [
            'signature' => 'data:image/png;base64,'.base64_encode('x'),
        ])->assertForbidden();

        $this->sebagaiKorlap($sa, $korlap)->post(route('project-attachments.store', $project), [
            'files' => [UploadedFile::fake()->create('x.pdf', 10)],
        ])->assertForbidden();

        $this->sebagaiKorlap($sa, $korlap)->post(route('invoices.sign', $project), [
            'signature' => 'data:image/png;base64,'.base64_encode('x'),
        ])->assertForbidden();
    }

    public function test_admin_pic_dan_sa_mode_admin_tetap_bisa_aksi_tersebut(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create(['admin_id' => $pic->id, 'nama_produksi' => 'Proyek Lancar', 'deadline' => now()->addDays(7), 'kuota' => 5]);
        $application = $this->aplikasi($project);
        $application->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($pic)->post(route('payments.addon', $application), [
            'label' => 'Transport', 'nominal' => 50000,
        ])->assertRedirect()->assertSessionHas('status');

        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->post(route('invoices.sign', $project), [
            'signature' => 'data:image/png;base64,'.self::PNG,
        ])->assertRedirect()->assertSessionHas('status');
    }
}

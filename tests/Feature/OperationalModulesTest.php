<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Invoice;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_buat_proyek_dengan_breakdown_dan_cover(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.projects.store'), [
            'nama_produksi' => 'Proyek Breakdown Test',
            'client_ph' => 'PH Visual Test',
            'deadline' => today()->addDays(5)->format('Y-m-d'),
            'kuota' => 10,
            'cover_path' => UploadedFile::fake()->image('cover.jpg'),
            'tanggal_shooting' => [today()->addDays(7)->format('Y-m-d')],
            'kelas' => [
                [
                    'nama_kelas' => 'Karakter A',
                    'budget_client' => 500000,
                    'kuota_kelas' => 5,
                    'jam_callsheet' => '07:00',
                    'jam_callingan' => '', // Harusnya auto -1 jam jadi 06:00
                    'tipe_continuity' => 'continuity',
                    'keterangan_scene' => 'Scene 1-3 warung',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.projects.index'));

        $project = CastingProject::where('nama_produksi', 'Proyek Breakdown Test')->first();
        $this->assertNotNull($project);
        $this->assertNotNull($project->cover_path);

        $class = $project->classes->first();
        $this->assertSame('07:00', $class->jam_callsheet);
        $this->assertSame('06:00', $class->jam_callingan);
        $this->assertSame('continuity', $class->tipe_continuity);
        $this->assertSame('Scene 1-3 warung', $class->keterangan_scene);
    }

    public function test_client_bisa_ajukan_proyek_dan_super_admin_acc(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Client ajukan proyek
        $response = $this->actingAs($client)->post(route('cd.projects.request.store'), [
            'nama_produksi' => 'Proyek Request Client',
            'deadline' => today()->addDays(10)->format('Y-m-d'),
            'kuota' => 8,
            'brief_catatan' => 'Butuh talent muda profesional.',
            'tanggal_shooting' => [today()->addDays(12)->format('Y-m-d')],
            'kelas' => [
                [
                    'nama_kelas' => 'Talent Muda',
                    'budget_client' => 400000,
                    'kuota_kelas' => 8,
                ],
            ],
        ]);

        $response->assertRedirect(route('cd.dashboard'));

        $project = CastingProject::where('nama_produksi', 'Proyek Request Client')->first();
        $this->assertNotNull($project);
        $this->assertSame('menunggu_acc', $project->client_request_status);
        $this->assertSame('ditutup', $project->status);

        // Super Admin ACC proyek
        $accResponse = $this->actingAs($superAdmin)->post(route('super-admin.projects.acc', $project));
        $accResponse->assertRedirect();

        $project->refresh();
        $this->assertSame('disetujui', $project->client_request_status);
        $this->assertSame('dibuka', $project->status);
    }

    public function test_super_admin_bisa_tolak_pengajuan_proyek_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $project = CastingProject::create([
            'nama_produksi' => 'Proyek Ditolak',
            'client_ph' => $client->name,
            'diajukan_oleh_client_id' => $client->id,
            'client_request_status' => 'menunggu_acc',
            'status' => 'ditutup',
            'deadline' => today()->addDays(7),
            'kuota' => 5,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.projects.reject', $project));
        $response->assertRedirect();

        $project->refresh();
        $this->assertSame('ditolak', $project->client_request_status);
        $this->assertSame('ditutup', $project->status);
    }

    public function test_hybrid_attendance_extras_selfie_dan_validasi_korlap(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Absensi Hybrid',
            'client_ph' => 'PH Test',
            'deadline' => today()->addDays(2),
            'kuota' => 5,
        ]);
        $shootingDate = $project->shootingDates()->create(['tanggal' => today()]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
        ]);

        // Extras upload selfie
        $selfieResponse = $this->actingAs($extrasUser)->post(route('extras.absensi.selfie', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $selfieResponse->assertRedirect();

        $attendance = Attendance::where('project_application_id', $application->id)
            ->where('event_shooting_date_id', $shootingDate->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('hadir', $attendance->status);
        $this->assertSame('menunggu', $attendance->status_validasi);
        $this->assertNotNull($attendance->foto_path);

        // Korlap validasi kehadiran
        $validasiResponse = $this->actingAs($korlap)->post(route('admin.absensi.validasi', $attendance));
        $validasiResponse->assertRedirect();

        $attendance->refresh();
        $this->assertSame('tervalidasi', $attendance->status_validasi);
        $this->assertSame($korlap->id, $attendance->divalidasi_oleh);

        // Korlap tolak validasi
        $tolakResponse = $this->actingAs($korlap)->post(route('admin.absensi.tolak', $attendance));
        $tolakResponse->assertRedirect();

        $attendance->refresh();
        $this->assertSame('tidak_hadir', $attendance->status);
    }

    public function test_admin_bisa_update_breakdown_applicant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Breakdown Applicant',
            'client_ph' => 'PH Test',
            'deadline' => today()->addDays(5),
            'kuota' => 5,
        ]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'direview_admin',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.applications.breakdown', $application), [
            'karakter' => 'Satpam Bank',
            'jam_callingan' => '05:30',
            'tipe_continuity' => 'continuity',
            'keterangan_scene' => 'Scene 4-6 Lobi Bank',
        ]);

        $response->assertRedirect();

        $application->refresh();
        $this->assertSame('Satpam Bank', $application->karakter);
        $this->assertSame('05:30', $application->jam_callingan);
        $this->assertSame('continuity', $application->tipe_continuity);
        $this->assertSame('Scene 4-6 Lobi Bank', $application->keterangan_scene);
    }

    public function test_prune_akun_mangkrak_hanya_hapus_akun_lebih_30_hari_tanpa_pendaftaran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Akun mangkrak >30 hari, tanpa pendaftaran, profil kosong
        $mangkrakUser = User::factory()->create([
            'role' => 'extras',
        ]);
        $mangkrakUser->created_at = now()->subDays(35);
        $mangkrakUser->save();

        $mangkrakProfile = ExtrasProfile::create([
            'user_id' => $mangkrakUser->id,
            'nik' => null,
            'foto_profil_path' => null,
        ]);
        $mangkrakProfile->created_at = now()->subDays(35);
        $mangkrakProfile->save();

        // Akun aktif punya pendaftaran (harus aman, tidak boleh ke-prune)
        $aktifUser = User::factory()->create([
            'role' => 'extras',
        ]);
        $aktifUser->created_at = now()->subDays(40);
        $aktifUser->save();

        $aktifProfile = ExtrasProfile::create([
            'user_id' => $aktifUser->id,
            'nik' => '3274011203950001',
            'foto_profil_path' => 'photos/test.jpg',
        ]);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Riil',
            'client_ph' => 'PH Aktif',
            'deadline' => today()->addDays(5),
            'kuota' => 5,
        ]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $aktifProfile->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.prune'));
        $response->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $mangkrakUser->id]);
        $this->assertDatabaseHas('users', ['id' => $aktifUser->id]);
    }

    public function test_dual_model_invoice_upload_dan_download(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Invoice Dual Model',
            'client_ph' => 'PH Test',
            'deadline' => today()->addDays(5),
            'kuota' => 5,
            'diajukan_oleh_client_id' => $client->id,
        ]);

        // Client upload custom PH invoice document
        $uploadResponse = $this->actingAs($client)->post(route('invoices.upload-custom', $project), [
            'custom_doc' => UploadedFile::fake()->create('voucher_ph.pdf', 100),
        ]);

        $uploadResponse->assertRedirect();

        $invoice = Invoice::where('casting_project_id', $project->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('custom_ph', $invoice->template_type);
        $this->assertNotNull($invoice->custom_doc_path);

        // Admin download custom PH document
        $downloadResponse = $this->actingAs($admin)->get(route('invoices.download-custom', $project));
        $downloadResponse->assertOk();
    }
}

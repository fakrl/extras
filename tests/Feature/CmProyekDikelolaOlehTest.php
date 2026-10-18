<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\ProjectAttachment;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SPEC CM: satu gerbang "proyek dikelola oleh", godmode SA dicabut.
 */
class CmProyekDikelolaOlehTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(?User $pic = null, ?User $ditugaskan = null): CastingProject
    {
        $pic ??= User::factory()->create(['role' => 'admin']);

        $project = CastingProject::create([
            'admin_id' => $pic->id,
            'nama_produksi' => 'Proyek CM '.uniqid(),
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
        $project->shootingDates()->create(['tanggal' => today()]);

        if ($ditugaskan) {
            $project->adminAssignments()->create(['user_id' => $ditugaskan->id, 'assigned_by' => $pic->id, 'status_log' => 'berjalan']);
        }

        return $project;
    }

    private function buatAplikasi(CastingProject $project): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
        ]);
    }

    public function test_admin_lain_ditolak_toggle_status_proyek(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);

        $this->actingAs($lain)->patch(route('admin.projects.toggle-status', $project))->assertStatus(403);
    }

    public function test_admin_lain_ditolak_set_grade(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->patch(route('admin.applications.grade', $app), ['grade' => 'A'])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_reject_kandidat(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);
        $app->update(['status_partisipasi' => 'diajukan']);

        $this->actingAs($lain)->patch(route('admin.applications.reject', $app), ['alasan_tolak' => 'Tidak sesuai'])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_bulk_action(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('admin.projects.applicants.bulk', $project), [
            'ids' => [$app->id],
            'aksi' => 'grade',
            'grade' => 'A',
        ])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_undang_extras(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $this->actingAs($lain)->post(route('admin.undangan.store', $extrasUser), [
            'casting_project_id' => $project->id,
        ])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_catat_biaya(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);

        $this->actingAs($lain)->post(route('admin.projects.expenses.store', $project), [
            'label' => 'Konsumsi',
            'nominal' => 100000,
            'tanggal' => today()->toDateString(),
        ])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_tandai_transfer_honor(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('payments.transfer', $app), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_ajukan_nego(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('admin.negotiations.ajukan', $app), ['nominal' => 100000])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_ttd_kontrak(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('contracts.sign', $app), ['signature' => 'x'])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_tandai_lunas_invoice(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);

        $this->actingAs($lain)->patch(route('admin.projects.invoice-lunas', $project), ['nominal' => 100000])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_selesaikan_sengketa(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('payments.selesaikan-sengketa', $app), ['catatan' => 'oke'])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_tambah_catatan(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('admin.applications.catatan', $app), ['jenis' => 'catatan', 'isi' => 'tes'])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_batalkan_undangan(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);

        $this->actingAs($lain)->post(route('admin.undangan.batal', $app))->assertStatus(403);
    }

    public function test_admin_lain_ditolak_edit_dan_portofolio_proyek(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);

        $this->actingAs($lain)->patch(route('admin.projects.update', $project), [])->assertStatus(403);
        $this->actingAs($lain)->patch(route('admin.projects.portofolio', $project), [])->assertStatus(403);
    }

    public function test_admin_lain_ditolak_store_lampiran_pengunggah_tetap_boleh_hapus_milik_sendiri(): void
    {
        Storage::fake('local');
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($pic);
        $project->update(['client_id' => $client->id]);

        $this->actingAs($lain)->post(route('project-attachments.store', $project), [
            'files' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
        ])->assertStatus(403);

        ProjectAttachment::unggah($project, [UploadedFile::fake()->create('milik-client.pdf', 10, 'application/pdf')], $client);
        ProjectAttachment::unggah($project, [UploadedFile::fake()->create('milik-pic.pdf', 10, 'application/pdf')], $pic);
        $fileClient = $project->attachments()->where('nama_asli', 'milik-client.pdf')->firstOrFail();
        $filePic = $project->attachments()->where('nama_asli', 'milik-pic.pdf')->firstOrFail();

        // PIC tidak bisa hapus lampiran milik Client.
        $this->actingAs($pic)->delete(route('project-attachments.destroy', $fileClient))->assertStatus(403);

        // Pengunggah (PIC) tetap boleh hapus uploadnya sendiri walau nanti PIC-nya diganti.
        $project->update(['admin_id' => $lain->id]);
        $this->actingAs($pic)->delete(route('project-attachments.destroy', $filePic))->assertRedirect();
        $this->assertModelMissing($filePic);
    }

    public function test_notifikasi_balasan_undangan_hanya_ke_pic_bukan_admin_lain(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $app = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diundang',
        ]);

        $this->actingAs($extrasUser)->post(route('extras.undangan.terima', $app))->assertRedirect();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pic->id, 'type' => InAppNotification::class]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $lain->id, 'type' => InAppNotification::class]);
    }

    public function test_admin_lain_bisa_get_proyek_tapi_tanpa_tombol_aksi(): void
    {
        $pic = User::factory()->create(['role' => 'admin', 'name' => 'Rina PIC']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);

        $response = $this->actingAs($lain)->get(route('admin.projects.show', $project));

        $response->assertOk();
        $response->assertSee('Lihat saja');
        $response->assertDontSee(route('admin.projects.edit', $project), false);
    }

    public function test_admin_lain_bisa_get_lineup_tapi_tanpa_tombol_aksi(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic);
        $this->buatAplikasi($project);

        $response = $this->actingAs($lain)->get(route('admin.projects.applicants', $project));

        $response->assertOk();
        $response->assertSee('Lihat saja');
    }

    public function test_admin_ditugaskan_bukan_pic_bisa_beraksi(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $staf = User::factory()->create(['role' => 'admin']);
        $project = $this->buatProyek($pic, $staf);
        $app = $this->buatAplikasi($project);

        $response = $this->actingAs($staf)->patch(route('admin.applications.grade', $app), ['grade' => 'A']);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_applications', ['id' => $app->id, 'grade' => 'A']);
    }

    public function test_korlap_tanpa_penugasan_ditolak_tandai_absen(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->buatProyek($pic);
        $app = $this->buatAplikasi($project);
        $shootingDate = $project->shootingDates->first();

        $this->actingAs($korlap)->post(route('admin.attendance.store', $app), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
        ])->assertStatus(403);
    }

    public function test_korlap_dengan_penugasan_bisa_tandai_absen(): void
    {
        $pic = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $project = $this->buatProyek($pic, $korlap);
        $app = $this->buatAplikasi($project);
        $shootingDate = $project->shootingDates->first();

        $this->actingAs($korlap)->post(route('admin.attendance.store', $app), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', ['project_application_id' => $app->id, 'status' => 'hadir']);
    }

    public function test_super_admin_tanpa_mode_get_boleh_post_ditolak(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = $this->buatProyek();

        $this->actingAs($sa)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($sa)->patch(route('admin.projects.toggle-status', $project))->assertStatus(403);
    }

    public function test_super_admin_mode_admin_bisa_aksi_dan_tercatat_sebagai_admin(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = $this->buatProyek();
        $app = $this->buatAplikasi($project);

        $response = $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])
            ->patch(route('admin.applications.grade', $app), ['grade' => 'A']);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_applications', ['id' => $app->id, 'grade' => 'A']);
        $this->assertTrue(
            ActivityLog::where('action', 'SET_EXTRAS_GRADE')->where('description', 'like', '%(sebagai Admin)%')->exists()
        );
    }

    public function test_super_admin_mode_korlap_tidak_bisa_aksi_rute_admin(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = $this->buatProyek();
        $app = $this->buatAplikasi($project);

        $this->actingAs($sa)->withSession(['sa_mode' => 'korlap'])
            ->patch(route('admin.applications.grade', $app), ['grade' => 'A'])
            ->assertStatus(403);
    }

    public function test_daftar_proyek_default_hanya_milik_sendiri_dan_tab_semua_lihat_semua(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $punya = $this->buatProyek($admin);
        $bukanPunya = $this->buatProyek();

        $default = $this->actingAs($admin)->get(route('admin.projects.index'));
        $default->assertSee($punya->nama_produksi);
        $default->assertDontSee($bukanPunya->nama_produksi);

        $semua = $this->actingAs($admin)->get(route('admin.projects.index', ['semua' => 1]));
        $semua->assertSee($bukanPunya->nama_produksi);
    }

    public function test_dashboard_admin_toggle_semua_proyek_sudah_dihapus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Semua proyek');
    }
}

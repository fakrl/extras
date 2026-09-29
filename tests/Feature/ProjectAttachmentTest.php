<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function lampiran(CastingProject $project, User $by): ProjectAttachment
    {
        ProjectAttachment::unggah($project, [UploadedFile::fake()->create('brief.pdf', 200, 'application/pdf')], $by);

        return $project->attachments()->first();
    }

    public function test_admin_upload_multi_file_tersimpan_di_disk_local_dan_tercatat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create();

        $this->actingAs($admin)->post(route('project-attachments.store', $project), [
            'files' => [
                UploadedFile::fake()->create('brief.pdf', 300, 'application/pdf'),
                UploadedFile::fake()->create('rundown.xlsx', 50),
            ],
            'keterangan' => 'Brief final',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $files = $project->attachments()->get();
        $this->assertCount(2, $files);
        $this->assertEqualsCanonicalizing(['brief.pdf', 'rundown.xlsx'], $files->pluck('nama_asli')->all());
        $files->each(fn ($f) => Storage::disk('local')->assertExists($f->path));
        $this->assertStringStartsWith("project-attachments/{$project->id}/", $files->first()->path);
        $this->assertSame($admin->id, $files->first()->uploaded_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'PROJECT_ATTACHMENT_UPLOADED', 'subject_type' => CastingProject::class, 'subject_id' => $project->id, 'user_id' => $admin->id]);
    }

    public function test_super_admin_bisa_upload_dan_tab_lampiran_tampil(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::factory()->create();

        $this->actingAs($sa)->post(route('project-attachments.store', $project), [
            'files' => [UploadedFile::fake()->image('lokasi.jpg')],
        ])->assertSessionHasNoErrors();

        $this->actingAs($sa)->get(route('admin.projects.show', [$project, 'tab' => 'lampiran']))
            ->assertOk()->assertSee('lokasi.jpg')->assertSee('Unggah Lampiran');
    }

    public function test_client_ter_assign_bisa_unduh_dan_lihat_di_halaman_jadwal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create();
        $project->cdAssignments()->create(['cd_user_id' => $client->id]);
        $file = $this->lampiran($project, $admin);

        $this->actingAs($client)->get(route('project-attachments.download', $file))
            ->assertOk()->assertDownload('brief.pdf');
        $this->actingAs($client)->get(route('cd.jadwal.show', $project))
            ->assertOk()->assertSee('brief.pdf');
    }

    public function test_client_pemilik_client_id_boleh_unduh(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create(['client_id' => $client->id]);
        $file = $this->lampiran($project, User::factory()->create(['role' => 'admin']));

        $this->actingAs($client)->get(route('project-attachments.download', $file))->assertOk();
    }

    public function test_client_proyek_lain_dan_extras_dan_korlap_ditolak(): void
    {
        $project = CastingProject::factory()->create();
        $file = $this->lampiran($project, User::factory()->create(['role' => 'admin']));

        $lain = User::factory()->create(['role' => 'client']);
        CastingProject::factory()->create()->cdAssignments()->create(['cd_user_id' => $lain->id]);

        foreach ([$lain, User::factory()->create(['role' => 'extras']), User::factory()->create(['role' => 'korlap'])] as $u) {
            $this->actingAs($u)->get(route('project-attachments.download', $file))->assertForbidden();
            $this->actingAs($u)->post(route('project-attachments.store', $project), [
                'files' => [UploadedFile::fake()->create('x.pdf', 10)],
            ])->assertForbidden();
        }
        $this->assertSame(1, $project->attachments()->count());
    }

    public function test_hapus_hanya_pengunggah_atau_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminLain = User::factory()->create(['role' => 'admin']);
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::factory()->create();

        $file = $this->lampiran($project, $admin);
        $this->actingAs($adminLain)->delete(route('project-attachments.destroy', $file))->assertForbidden();
        $this->assertModelExists($file);

        $this->actingAs($sa)->delete(route('project-attachments.destroy', $file))->assertRedirect();
        $this->assertModelMissing($file);
        Storage::disk('local')->assertMissing($file->path);
        $this->assertDatabaseHas('activity_logs', ['action' => 'PROJECT_ATTACHMENT_DELETED', 'subject_id' => $project->id, 'user_id' => $sa->id]);

        $file2 = $this->lampiran($project, $admin);
        $this->actingAs($admin)->delete(route('project-attachments.destroy', $file2))->assertRedirect();
        $this->assertModelMissing($file2);
    }

    public function test_validasi_mimes_ukuran_dan_wajib_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create();
        $url = route('project-attachments.store', $project);

        $this->actingAs($admin)->post($url, [])->assertSessionHasErrors('files');
        $this->actingAs($admin)->post($url, ['files' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('files.0');
        $this->actingAs($admin)->post($url, ['files' => [UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf')]])->assertSessionHasErrors('files.0');
        $this->assertSame(0, $project->attachments()->count());
    }

    public function test_pengajuan_proyek_client_bisa_lampirkan_file(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->post(route('cd.projects.request.store'), [
            'nama_produksi' => 'Iklan Lampiran',
            'deadline' => today()->addWeek()->toDateString(),
            'kuota' => 5,
            'brief_catatan' => 'Brief',
            'files' => [UploadedFile::fake()->create('moodboard.pdf', 100, 'application/pdf')],
        ])->assertRedirect(route('cd.dashboard'));

        $project = CastingProject::where('nama_produksi', 'Iklan Lampiran')->firstOrFail();
        $file = $project->attachments()->firstOrFail();
        $this->assertSame('moodboard.pdf', $file->nama_asli);
        $this->assertSame($client->id, $file->uploaded_by);
        Storage::disk('local')->assertExists($file->path);
        $this->assertTrue(ActivityLog::where('action', 'PROJECT_ATTACHMENT_UPLOADED')->where('subject_id', $project->id)->exists());
    }

    public function test_ukuran_label(): void
    {
        $this->assertSame('200 KB', (new ProjectAttachment(['ukuran' => 204800]))->ukuranLabel());
        $this->assertSame('2,5 MB', (new ProjectAttachment(['ukuran' => 2621440]))->ukuranLabel());
    }
}

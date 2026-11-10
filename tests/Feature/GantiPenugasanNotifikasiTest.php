<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GantiPenugasanNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function proyek(User $pic): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $pic->id,
            'nama_produksi' => 'Proyek Alih Tugas',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    public function test_pic_lama_dinotifikasi_saat_dialihkan_dan_pic_baru_tetap_dinotifikasi(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $adminLama = User::factory()->create(['role' => 'admin']);
        $adminBaru = User::factory()->create(['role' => 'admin', 'name' => 'Budi Baru']);
        $project = $this->proyek($adminLama);

        $this->actingAs($sa)->patch(route('admin.projects.pengelola', $project), [
            'admin_id' => $adminBaru->id,
        ])->assertRedirect();

        $notifLama = $adminLama->notifications()->where('type', InAppNotification::class)->first();
        $this->assertNotNull($notifLama);
        $this->assertStringContainsString($project->namaKode(), $notifLama->data['pesan']);
        $this->assertStringContainsString('Budi Baru', $notifLama->data['pesan']);

        $notifBaru = $adminBaru->notifications()->where('type', InAppNotification::class)->first();
        $this->assertNotNull($notifBaru);
        $this->assertStringContainsString('ditetapkan sebagai Admin PIC', $notifBaru->data['pesan']);
    }

    public function test_pic_lama_dinotifikasi_pesan_dicabut_saat_dikosongkan(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $adminLama = User::factory()->create(['role' => 'admin']);
        $project = $this->proyek($adminLama);

        $this->actingAs($sa)->patch(route('admin.projects.pengelola', $project), [
            'admin_id' => '',
        ])->assertRedirect();

        $notifLama = $adminLama->notifications()->where('type', InAppNotification::class)->first();
        $this->assertNotNull($notifLama);
        $this->assertStringContainsString('telah dicabut', $notifLama->data['pesan']);
    }

    public function test_korlap_lama_dinotifikasi_saat_dialihkan_dan_korlap_baru_tetap_dinotifikasi(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin']);
        $korlapLama = User::factory()->create(['role' => 'korlap']);
        $korlapBaru = User::factory()->create(['role' => 'korlap', 'name' => 'Sari Korlap']);
        $project = $this->proyek($pic);
        $project->adminAssignments()->create(['user_id' => $korlapLama->id, 'assigned_by' => $pic->id]);

        $this->actingAs($sa)->patch(route('admin.projects.pengelola', $project), [
            'korlap_id' => $korlapBaru->id,
        ])->assertRedirect();

        $notifLama = $korlapLama->notifications()->where('type', InAppNotification::class)->first();
        $this->assertNotNull($notifLama);
        $this->assertStringContainsString($project->namaKode(), $notifLama->data['pesan']);
        $this->assertStringContainsString('Sari Korlap', $notifLama->data['pesan']);

        $notifBaru = $korlapBaru->notifications()->where('type', InAppNotification::class)->first();
        $this->assertNotNull($notifBaru);
        $this->assertStringContainsString('ditetapkan sebagai Koordinator Lapangan', $notifBaru->data['pesan']);
    }
}

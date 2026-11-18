<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerisiKuotaKonsistenTest extends TestCase
{
    use RefreshDatabase;

    private function extras(): ProjectApplication
    {
        static $i = 0;
        $i++;
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::create(['user_id' => $user->id]);

        return new ProjectApplication(['extras_id' => $profile->id]);
    }

    public function test_dashboard_admin_dan_daftar_proyek_pakai_definisi_terisi_yang_sama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'nama_produksi' => 'Proyek Uji Terisi', 'status' => 'dibuka',
            'client_request_status' => 'disetujui', 'admin_id' => $admin->id,
            'kuota' => 10, 'deadline' => today()->addDays(10),
        ]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Umum', 'kuota_kelas' => 5]);

        // BD.7 ground truth: terisi = semua status KECUALI ditolak/dibatalkan.
        // 'diajukan' sengaja dipakai karena TIDAK masuk daftar sempit lama dashboard.
        foreach (['diajukan', 'nego_fee', 'deal', 'ditolak', 'dibatalkan'] as $status) {
            $app = $this->extras();
            $app->casting_project_id = $project->id;
            $app->casting_project_class_id = $kelas->id;
            $app->status_partisipasi = $status;
            $app->save();
        }

        $dashboard = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $bottleneck = $dashboard->viewData('proyekBottleneck')->firstWhere('id', $project->id);

        $list = $this->actingAs($admin)->get(route('admin.projects.index'));
        $list->assertOk();

        // 3 aplikasi valid (diajukan, nego_fee, deal) dari 5 kuota kelas - BD.7.
        $this->assertSame(3, $bottleneck->terisi_kuota, 'Dashboard harus hitung 3 terisi (BD.7), bukan cuma status lanjut.');
        $list->assertSee('3 / 5', false);
    }
}

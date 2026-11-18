<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KorlapRingkasanBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_breakdown_kehadiran_yang_tampil_menjumlah_ke_total_panggilan(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap']);
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create(['admin_id' => $admin->id, 'nama_produksi' => 'Proyek Breakdown', 'deadline' => now()->addDays(7), 'kuota' => 10, 'status' => 'dibuka', 'client_request_status' => 'disetujui']);
        $sd = $project->shootingDates()->create(['tanggal' => today(), 'lokasi' => 'Lokasi', 'jam_mulai' => '07:00']);
        AdminProjectAssignment::create(['casting_project_id' => $project->id, 'user_id' => $korlap->id, 'assigned_by' => $admin->id]);

        // 2 hadir tervalidasi, 2 menunggu validasi, 2 tidak hadir (tervalidasi), 8 belum absen = 14 total panggilan
        foreach (['hadir', 'hadir', 'menunggu', 'menunggu', 'tidak_hadir', 'tidak_hadir', 'belum', 'belum', 'belum', 'belum', 'belum', 'belum', 'belum', 'belum'] as $i => $kondisi) {
            $u = User::factory()->create(['role' => 'extras']);
            $e = ExtrasProfile::create(['user_id' => $u->id, 'nama_asli' => 'X'.$i, 'nik' => (string) random_int(1e15, 9e15)]);
            $app = ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $e->id, 'status_partisipasi' => 'lolos']);
            match ($kondisi) {
                'hadir' => Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'status_validasi' => 'tervalidasi', 'dicatat_oleh' => $u->id]),
                'menunggu' => Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'status_validasi' => 'menunggu', 'dicatat_oleh' => $u->id]),
                'tidak_hadir' => Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'tidak_hadir', 'status_validasi' => 'tervalidasi', 'dicatat_oleh' => $u->id]),
                default => null,
            };
        }

        $response = $this->actingAs($korlap)->get(route('admin.dashboard'))->assertOk();
        $stat = $response->viewData('statKorlap');
        $this->assertSame(14, $stat['total_panggilan']);
        $this->assertSame(2, $stat['total_hadir']);
        $this->assertSame(2, $stat['total_menunggu_val']);
        $this->assertSame(2, $stat['total_tidak_hadir']);
        $this->assertSame(8, $stat['total_belum_absen']);
        $this->assertSame(14, $stat['total_hadir'] + $stat['total_menunggu_val'] + $stat['total_tidak_hadir'] + $stat['total_belum_absen']);

        // sebelum perbaikan, baris "Tidak Hadir" cuma ada di popover rincian (viz-legend yang selalu
        // terlihat cuma Hadir/Validasi/Belum Absen - total keliatan 2+2+8=12, bukan 14 dari total_panggilan)
        $response->assertSee('2/14', false);
        $this->assertStringContainsString('viz-dot" style="background: #DC2626;"></span>Tidak Hadir', $response->getContent());
    }
}

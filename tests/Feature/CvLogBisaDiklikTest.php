<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\Contract;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CV.3: ActivityLog::subjectUrl() lengkap tiap jenis subjek, SA buka tautan tanpa masuk mode. */
class CvLogBisaDiklikTest extends TestCase
{
    use RefreshDatabase;

    private function lamaran(string $status = 'kontrak_ditandatangani'): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $profil = ExtrasProfile::factory()->create();
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $profil->id, 'status_partisipasi' => $status]);

        return [$admin, $p, $app, $profil];
    }

    public function test_subject_url_attendance_ke_absensi_dengan_anchor(): void
    {
        [$admin, $p, $app, $profil] = $this->lamaran('lolos');
        $sd = $p->shootingDates()->create(['tanggal' => today()]);
        $att = Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'dicatat_oleh' => $profil->user_id, 'foto_path' => 'x.jpg', 'status_validasi' => 'menunggu']);
        $log = ActivityLog::record('SUBMIT_SELFIE_ATTENDANCE', 'tes', $att);

        $url = $log->subjectUrl();
        $this->assertStringContainsString(route('admin.attendance.index', ['project' => $p->id, 'tanggal' => $sd->id], false), $url);
        $this->assertStringContainsString('#app-'.$app->id, $url);
    }

    public function test_subject_url_project_application_ke_lineup_ber_anchor(): void
    {
        [$admin, $p, $app] = $this->lamaran('diajukan');
        $log = ActivityLog::record('SET_EXTRAS_GRADE', 'tes', $app);

        $this->assertStringContainsString(route('admin.projects.applicants', $p->id, false), $log->subjectUrl());
        $this->assertStringContainsString('#app-'.$app->id, $log->subjectUrl());
    }

    public function test_subject_url_pembayaran_ke_halaman_pembayaran(): void
    {
        [$admin, $p, $app] = $this->lamaran('kontrak_ditandatangani');
        $app->payment()->create(['status' => 'belum_dibayar']);
        $log = ActivityLog::record('UPLOAD_PAYOUT_TRANSFER', 'tes', $app);

        $this->assertSame(route('payments.show', $app), $log->subjectUrl());
    }

    public function test_subject_url_contract(): void
    {
        [$admin, $p, $app] = $this->lamaran('lolos');
        $contract = Contract::create(['project_application_id' => $app->id]);
        $log = ActivityLog::record('SIGN_CONTRACT', 'tes', $contract);

        $this->assertSame(route('contracts.show', $app), $log->subjectUrl());
    }

    public function test_subject_url_invoice_dari_casting_project(): void
    {
        [$admin, $p, $app] = $this->lamaran();
        $log = ActivityLog::record('SIGN_INVOICE', 'tes', $p);

        $this->assertSame(route('invoices.show', $p), $log->subjectUrl());
    }

    public function test_subject_terhapus_tanpa_tautan(): void
    {
        [$admin, $p, $app] = $this->lamaran();
        $log = ActivityLog::record('UPDATE_PROJECT_PIC', 'tes', $p);
        $p->delete();
        $log->refresh();

        $this->assertNull($log->subjectUrl());
    }

    public function test_sa_buka_tautan_log_tanpa_masuk_mode_dan_tanpa_tombol_aksi(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        [$admin, $p, $app] = $this->lamaran('kontrak_ditandatangani');
        $app->payment()->create(['status' => 'belum_dibayar']);

        $r = $this->actingAs($sa)->get(route('payments.show', $app));

        $r->assertOk()->assertDontSee('Unggah Bukti Transfer');
        $this->assertNull(session('sa_mode'));
    }

    public function test_seluruh_baris_log_bisa_diklik_dan_pelaku_tampil_username(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin', 'username' => 'fakrul']);
        [$admin, $p, $app] = $this->lamaran();
        ActivityLog::record('UPDATE_PROJECT_PIC', 'Mengubah PIC proyek', $p, [], $sa);

        $r = $this->actingAs($sa)->get(route('super-admin.activity-logs'));

        $r->assertOk()->assertSee('@fakrul')->assertSee('href="'.e(route('admin.projects.show', $p)).'"', false);
    }
}

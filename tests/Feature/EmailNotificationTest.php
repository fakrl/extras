<?php

namespace Tests\Feature;

use App\Mail\HasilSeleksiMail;
use App\Mail\KonfirmasiFeeMail;
use App\Mail\KontrakSiapTtdMail;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(string $status = 'diajukan'): ProjectApplication
    {
        $adminUser = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test', 'nama_asli' => 'Nama Asli Test']);

        $project = CastingProject::create([
            'admin_id' => $adminUser->id,
            'nama_produksi' => 'Kado Untuk Ibu',
            'client_ph' => 'Starvision',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ]);
    }

    public function test_tolak_dini_mengirim_hasil_seleksi_mail_dan_mencatat_notifikasi(): void
    {
        Mail::fake();

        $application = $this->buatAplikasi('diajukan');
        $application->tolakDini('Tidak sesuai kriteria');

        Mail::assertQueued(HasilSeleksiMail::class);

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'hasil_seleksi',
            'status' => 'terkirim',
        ]);
    }

    public function test_cd_approve_mengirim_hasil_seleksi_mail(): void
    {
        Mail::fake();

        $application = $this->buatAplikasi('diajukan_ke_cd');
        $cd = User::factory()->create(['role' => 'casting_director']);

        $response = $this->actingAs($cd)->post('/cd/reviews', [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ]);

        $response->assertRedirect();
        Mail::assertQueued(HasilSeleksiMail::class);

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'hasil_seleksi',
            'status' => 'terkirim',
        ]);
    }

    public function test_ajukan_fee_awal_mengirim_konfirmasi_fee_mail_ke_extras(): void
    {
        Mail::fake();

        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;

        $response = $this->actingAs($admin)->post("/admin/applications/{$application->id}/nego/ajukan", [
            'nominal' => 200000,
        ]);

        $response->assertRedirect();
        Mail::assertQueued(KonfirmasiFeeMail::class);

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'nego_fee',
            'status' => 'terkirim',
        ]);
    }

    public function test_counter_fee_dari_extras_mengirim_ke_admin(): void
    {
        Mail::fake();

        $application = $this->buatAplikasi('nego_fee');
        $application->ajukanFeeAwal(200000);

        Mail::fake();

        $extras = $application->extras->user;
        $response = $this->actingAs($extras)->post("/extras/nego/{$application->id}/counter", [
            'nominal' => 250000,
        ]);

        $response->assertRedirect();
        Mail::assertQueued(KonfirmasiFeeMail::class);

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->castingProject->admin_id,
            'jenis' => 'nego_fee',
            'status' => 'terkirim',
        ]);
    }

    public function test_generate_kontrak_mengirim_kontrak_siap_ttd_mail_ke_extras_dan_admin(): void
    {
        Mail::fake();
        Storage::fake('local');

        $application = $this->buatAplikasi('lolos');
        $application->extras->lengkapiKtp('3201234567890099', 'BCA 000');
        $extras = $application->extras->user;

        $response = $this->actingAs($extras)->get("/kontrak/{$application->id}");

        $response->assertOk();
        Mail::assertQueued(KontrakSiapTtdMail::class, 2);

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $extras->id,
            'jenis' => 'kontrak_siap_ttd',
            'status' => 'terkirim',
        ]);
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->castingProject->admin_id,
            'jenis' => 'kontrak_siap_ttd',
            'status' => 'terkirim',
        ]);
    }
}

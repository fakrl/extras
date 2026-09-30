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
        $adminUser = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'nama_asli' => 'Nama Asli Test']);

        $project = CastingProject::create([
            'admin_id' => $adminUser->id,
            'nama_produksi' => 'Kado Untuk Ibu',
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

        $this->assertNotifikasi($application->extras->user_id, 'hasil_seleksi', ['email' => 'terkirim']);
    }

    public function test_cd_approve_mengirim_hasil_seleksi_mail(): void
    {
        Mail::fake();

        $application = $this->buatAplikasi('diajukan_ke_cd');
        $cd = User::factory()->create(['role' => 'client']);
        $application->castingProject->update(['client_id' => $cd->id]);

        $response = $this->actingAs($cd)->post('/cd/reviews', [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
            'grade_cd' => 'A',
        ]);

        $response->assertRedirect();
        Mail::assertQueued(HasilSeleksiMail::class);

        $this->assertNotifikasi($application->extras->user_id, 'hasil_seleksi', ['email' => 'terkirim']);
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

        $this->assertNotifikasi($application->extras->user_id, 'nego_fee', ['email' => 'terkirim']);
    }

    public function test_counter_fee_dari_extras_mengirim_ke_admin(): void
    {
        Mail::fake();

        // SPEC AY.1.11: ajukanFeeAwal() sekarang guard status diajukan/direview_admin,
        // jadi seed di sini pakai status sebelum nego (method sendiri yang transisi ke nego_fee).
        $application = $this->buatAplikasi('direview_admin');
        $application->ajukanFeeAwal(200000);

        Mail::fake();

        $extras = $application->extras->user;
        $response = $this->actingAs($extras)->post("/extras/nego/{$application->id}/counter", [
            'nominal' => 250000,
        ]);

        $response->assertRedirect();
        Mail::assertQueued(KonfirmasiFeeMail::class);

        $this->assertNotifikasi($application->castingProject->admin_id, 'nego_fee', ['email' => 'terkirim']);
    }

    public function test_generate_kontrak_mengirim_kontrak_siap_ttd_mail_ke_extras_dan_admin(): void
    {
        Mail::fake();
        Storage::fake('local');

        $application = $this->buatAplikasi('lolos');
        $extras = $application->extras->user;

        $this->actingAs($extras)->post(route('extras.kontrak.simpan-ktp', $application), ['nik' => '3201234567890099'])
            ->assertRedirect(route('contracts.show', $application));

        Mail::assertQueued(KontrakSiapTtdMail::class, 2);

        $this->assertNotifikasi($extras->id, 'kontrak_siap_ttd', ['email' => 'terkirim']);
        $this->assertNotifikasi($application->castingProject->admin_id, 'kontrak_siap_ttd', ['email' => 'terkirim']);
    }
}

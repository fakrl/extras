<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotification;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(string $status = 'diajukan', ?string $nomorWa = '081234567890'): ProjectApplication
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras', 'nomor_wa' => $nomorWa]);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'nama_asli' => 'Nama Asli Test']);

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

    public function test_kirim_memanggil_endpoint_node_dengan_payload_benar(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $hasil = app(WhatsAppService::class)->kirim('628123456789', 'Halo tes');

        $this->assertTrue($hasil);
        Http::assertSent(fn ($request) => $request['nomor'] === '628123456789' && $request['pesan'] === 'Halo tes');
    }

    public function test_kirim_return_false_kalau_node_service_gagal(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => false], 500)]);

        $hasil = app(WhatsAppService::class)->kirim('628123456789', 'Halo tes');

        $this->assertFalse($hasil);
    }

    public function test_apply_mengirim_wa_konfirmasi_dan_mencatat_log(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $extrasUser = User::factory()->create(['role' => 'extras', 'nomor_wa' => '081234567890']);
        ExtrasProfile::create(['user_id' => $extrasUser->id, 'foto_profil_path' => 'extras/foto.jpg', 'usia' => 25, 'gender' => 'Pria', 'tinggi_badan' => 170]);
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Kado Untuk Ibu',
            'client_ph' => 'Starvision',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $response = $this->actingAs($extrasUser)->post("/extras/lowongan/{$project->id}/daftar");

        $response->assertRedirect();
        $this->assertNotifikasi($extrasUser->id, 'konfirmasi_apply', ['wa' => 'terkirim']);
    }

    public function test_hasil_seleksi_mencatat_log_wa_selain_email(): void
    {
        Mail::fake();
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $application = $this->buatAplikasi('diajukan');
        $application->tolakDini('Tidak sesuai kriteria');

        $this->assertNotifikasi($application->extras->user_id, 'hasil_seleksi', ['wa' => 'terkirim']);
        $this->assertNotifikasi($application->extras->user_id, 'hasil_seleksi', ['email' => 'terkirim']);
    }

    public function test_kontrak_siap_ttd_mencatat_log_wa_ke_extras_dan_admin(): void
    {
        Mail::fake();
        Storage::fake('local');
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $application = $this->buatAplikasi('lolos');
        $extras = $application->extras->user;

        $this->actingAs($extras)->post(route('extras.kontrak.simpan-ktp', $application), ['nik' => '3201234567890099'])
            ->assertRedirect();

        $this->assertNotifikasi($extras->id, 'kontrak_siap_ttd', ['wa' => 'terkirim']);
        $this->assertArrayHasKey('wa', $this->notifikasi($application->castingProject->admin_id, 'kontrak_siap_ttd')[0]);
    }

    /**
     * Gap coverage (review DEV-NOTES): jalur gagal yang sudah ditest
     * sebelumnya cuma (a) nomor_wa null (skip, tidak sampai HTTP call) dan
     * (b) dispatch() sendiri throw (WhatsAppDispatchFailureTest). Belum ada
     * yang test job SendWhatsAppNotification::handle() sendiri ketika
     * nomor_wa ADA tapi HTTP call ke Node service-nya gagal (500) - jalur
     * try/catch di dalam handle() ini sendiri.
     */
    public function test_job_handle_mencatat_gagal_saat_http_ke_node_gagal(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => false], 500)]);

        $user = User::factory()->create(['role' => 'extras', 'nomor_wa' => '628123456789']);

        $notif = new InAppNotification('Hasil Seleksi', 'pesan', null, ['jenis' => 'hasil_seleksi', 'wa' => null]);
        $notif->id = 'b0000000-0000-4000-8000-000000000001';
        $user->notify($notif);

        (new SendWhatsAppNotification($user, 'hasil_seleksi', 'pesan test', $notif->id))->handle(app(WhatsAppService::class));

        $this->assertNotifikasi($user->id, 'hasil_seleksi', ['wa' => 'gagal']);
    }

    public function test_nomor_wa_null_tidak_mengirim_dan_mencatat_gagal(): void
    {
        Http::fake();

        $application = $this->buatAplikasi('diajukan', null);
        $application->kirimKonfirmasiApply();

        $this->assertNotifikasi($application->extras->user_id, 'konfirmasi_apply', ['wa' => 'gagal']);
        Http::assertNothingSent();
    }

    public function test_nomor_wa_dinormalisasi_ke_format_62(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'nomor_wa' => '081234567890']);
        $this->assertSame('6281234567890', $user->nomor_wa);

        $user2 = User::factory()->create(['role' => 'extras', 'nomor_wa' => '+6281234567891']);
        $this->assertSame('6281234567891', $user2->nomor_wa);
    }
}

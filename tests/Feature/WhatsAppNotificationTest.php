<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
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
        $adminUser = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras', 'nomor_wa' => $nomorWa]);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

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
        ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Kado Untuk Ibu',
            'client_ph' => 'Starvision',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $response = $this->actingAs($extrasUser)->post("/extras/lowongan/{$project->id}/daftar");

        $response->assertRedirect();
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $extrasUser->id,
            'jenis' => 'konfirmasi_apply',
            'channel' => 'whatsapp',
            'status' => 'terkirim',
        ]);
    }

    public function test_hasil_seleksi_mencatat_log_wa_selain_email(): void
    {
        Mail::fake();
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $application = $this->buatAplikasi('diajukan');
        $application->tolakDini('Tidak sesuai kriteria');

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'hasil_seleksi',
            'channel' => 'whatsapp',
            'status' => 'terkirim',
        ]);
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'hasil_seleksi',
            'channel' => 'email',
            'status' => 'terkirim',
        ]);
    }

    public function test_kontrak_siap_ttd_mencatat_log_wa_ke_extras_dan_admin(): void
    {
        Mail::fake();
        Storage::fake('local');
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $application = $this->buatAplikasi('lolos');
        $application->extras->lengkapiKtp('3201234567890099', 'BCA 000');
        $extras = $application->extras->user;

        $response = $this->actingAs($extras)->get("/kontrak/{$application->id}");

        $response->assertOk();
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $extras->id,
            'jenis' => 'kontrak_siap_ttd',
            'channel' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->castingProject->admin_id,
            'jenis' => 'kontrak_siap_ttd',
            'channel' => 'whatsapp',
        ]);
    }

    public function test_nomor_wa_null_tidak_mengirim_dan_mencatat_gagal(): void
    {
        Http::fake();

        $application = $this->buatAplikasi('diajukan', null);
        $application->kirimKonfirmasiApply();

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'konfirmasi_apply',
            'channel' => 'whatsapp',
            'status' => 'gagal',
        ]);
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

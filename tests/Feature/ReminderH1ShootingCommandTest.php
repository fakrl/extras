<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReminderH1ShootingCommandTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyekDenganShootingBesok(): CastingProject
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Kado Untuk Ibu',
            'client_ph' => 'Starvision',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $project->shootingDates()->create(['tanggal' => now()->addDay()]);

        return $project;
    }

    private function buatAplikasi(CastingProject $project, string $status, ?string $nomorWa = '081234567890'): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras', 'nomor_wa' => $nomorWa]);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ]);
    }

    public function test_kirim_reminder_ke_extras_status_deal_untuk_shooting_besok(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $project = $this->buatProyekDenganShootingBesok();
        $deal = $this->buatAplikasi($project, 'deal');

        Artisan::call('reminder:h1-shooting');

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $deal->extras->user_id,
            'jenis' => 'reminder_h1',
            'channel' => 'whatsapp',
            'status' => 'terkirim',
        ]);
    }

    public function test_tidak_kirim_reminder_untuk_status_nego_atau_ditolak_atau_dibatalkan(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $project = $this->buatProyekDenganShootingBesok();
        $nego = $this->buatAplikasi($project, 'nego_fee');
        $ditolak = $this->buatAplikasi($project, 'ditolak');
        $dibatalkan = $this->buatAplikasi($project, 'dibatalkan');

        Artisan::call('reminder:h1-shooting');

        foreach ([$nego, $ditolak, $dibatalkan] as $application) {
            $this->assertDatabaseMissing('notifications_log', [
                'user_id' => $application->extras->user_id,
                'jenis' => 'reminder_h1',
            ]);
        }
    }

    public function test_tidak_kirim_untuk_shooting_yang_bukan_besok(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Lain Hari',
            'client_ph' => 'Starvision',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
        $project->shootingDates()->create(['tanggal' => now()->addDays(3)]);
        $application = $this->buatAplikasi($project, 'deal');

        Artisan::call('reminder:h1-shooting');

        $this->assertDatabaseMissing('notifications_log', [
            'user_id' => $application->extras->user_id,
            'jenis' => 'reminder_h1',
        ]);
    }

    public function test_nomor_wa_null_dicatat_gagal_tanpa_mengganggu_extras_lain(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true], 200)]);

        $project = $this->buatProyekDenganShootingBesok();
        $tanpaNomor = $this->buatAplikasi($project, 'deal', null);
        $adaNomor = $this->buatAplikasi($project, 'lolos', '081234567891');

        Artisan::call('reminder:h1-shooting');

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $tanpaNomor->extras->user_id,
            'jenis' => 'reminder_h1',
            'channel' => 'whatsapp',
            'status' => 'gagal',
        ]);
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $adaNomor->extras->user_id,
            'jenis' => 'reminder_h1',
            'channel' => 'whatsapp',
            'status' => 'terkirim',
        ]);
    }
}

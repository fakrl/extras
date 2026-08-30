<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fix SPEC.md Item 1 (30 Agu 2026 pagi): ProjectApplication::
 * pastikanMasihBisaNego() sebelumnya lupa masukin kontrak_ditandatangani/
 * selesai_produksi/dibatalkan ke blocklist status — nego fee bisa dibuka
 * lagi & fee_final ditimpa SETELAH kontrak sudah ditandatangani kedua
 * pihak, dan ContractController::sign() bakal regenerate PDF dengan
 * angka baru, menimpa PDF yang sudah sah ditandatangani.
 */
class NegoFeeGateSetelahKontrakTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(User $admin, string $status): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $extras->id,
            'status_partisipasi' => $status, 'fee_final' => 200000,
        ]);
        $application->feeNegotiations()->create([
            'round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 200000, 'aksi' => 'terima',
        ]);

        return $application;
    }

    public static function statusBaruYangHarusDiblokir(): array
    {
        return [
            ['kontrak_ditandatangani'],
            ['selesai_produksi'],
            ['dibatalkan'],
        ];
    }

    #[DataProvider('statusBaruYangHarusDiblokir')]
    public function test_admin_counter_diblokir_untuk_status_baru(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($admin)->post("/admin/applications/{$application->id}/nego/counter", ['nominal' => 999999]);

        $response->assertStatus(422);
        $this->assertEquals(200000, $application->fresh()->fee_final);
    }

    #[DataProvider('statusBaruYangHarusDiblokir')]
    public function test_admin_terima_diblokir_untuk_status_baru(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($admin)->post("/admin/applications/{$application->id}/nego/terima", ['nominal' => 999999]);

        $response->assertStatus(422);
        $this->assertEquals(200000, $application->fresh()->fee_final);
    }

    #[DataProvider('statusBaruYangHarusDiblokir')]
    public function test_admin_tolak_diblokir_untuk_status_baru(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($admin)->post("/admin/applications/{$application->id}/nego/tolak");

        $response->assertStatus(422);
        $this->assertSame($status, $application->fresh()->status_partisipasi);
    }

    #[DataProvider('statusBaruYangHarusDiblokir')]
    public function test_extras_counter_diblokir_untuk_status_baru(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($application->extras->user)
            ->post("/extras/nego/{$application->id}/counter", ['nominal' => 999999]);

        $response->assertStatus(422);
        $this->assertEquals(200000, $application->fresh()->fee_final);
    }

    #[DataProvider('statusBaruYangHarusDiblokir')]
    public function test_extras_terima_diblokir_untuk_status_baru(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($application->extras->user)
            ->post("/extras/nego/{$application->id}/terima");

        $response->assertStatus(422);
        $this->assertEquals(200000, $application->fresh()->fee_final);
    }

    public static function statusLamaYangSudahDiblokir(): array
    {
        return [
            ['deal'], ['ditolak'], ['diajukan_ke_cd'], ['direview_cd'], ['lolos'],
        ];
    }

    #[DataProvider('statusLamaYangSudahDiblokir')]
    public function test_regresi_status_lama_tetap_diblokir(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($admin)->post("/admin/applications/{$application->id}/nego/counter", ['nominal' => 999999]);

        $response->assertStatus(422);
    }

    public static function statusYangMasihBolehNego(): array
    {
        return [
            ['diajukan'], ['direview_admin'], ['nego_fee'],
        ];
    }

    #[DataProvider('statusYangMasihBolehNego')]
    public function test_regresi_status_aktif_masih_bisa_nego(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, $status);

        $response = $this->actingAs($admin)->post("/admin/applications/{$application->id}/nego/counter", ['nominal' => 300000]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $rounds = $application->feeNegotiations()->get();
        $this->assertEquals(2, $rounds->count());
        $this->assertEquals(300000, $rounds->last()->nominal);
    }

    public function test_ajukan_awal_tetap_terblokir_lewat_pengecekan_riwayat_existing(): void
    {
        // Bukan bug baru Item 1, tapi dicatat: ajukanAwal() tidak pernah
        // panggil pastikanMasihBisaNego() sama sekali — proteksinya cuma
        // "sudah ada riwayat nego belum". Ini SELALU true untuk aplikasi
        // yang sudah lewat status deal (terimaFee() selalu bikin baris
        // nego dulu), jadi tidak reachable lewat alur normal. Test ini
        // membuktikan proteksi existing itu memang menutup celah yang sama,
        // bukan menguji fix baru.
        $admin = User::factory()->create(['role' => 'admin_default']);
        $application = $this->buatAplikasi($admin, 'kontrak_ditandatangani');

        $response = $this->actingAs($admin)
            ->post("/admin/applications/{$application->id}/nego/ajukan", ['nominal' => 999999]);

        $response->assertSessionHas('status', 'Fee awal sudah pernah diajukan untuk pendaftar ini.');
        $this->assertEquals(200000, $application->fresh()->fee_final);
    }
}

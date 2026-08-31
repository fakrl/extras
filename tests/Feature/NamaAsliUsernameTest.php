<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\Contract;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bagian D1 & D2: nama_asli dikumpulkan bareng alias di halaman profil,
 * dipakai sebagai nama penandatangan di PDF kontrak, plus username unik
 * yang tampil sebagai "Alias (@username)" ke Admin/CD.
 */
class NamaAsliUsernameTest extends TestCase
{
    use RefreshDatabase;

    private function buatExtras(array $profil = [], array $akun = []): ExtrasProfile
    {
        $user = User::factory()->create($akun + ['role' => 'extras']);

        return ExtrasProfile::create($profil + ['user_id' => $user->id, 'alias' => 'Alias Lama']);
    }

    private function payloadProfil(array $override = []): array
    {
        return $override + [
            'alias' => 'Alias Baru',
            'nama_asli' => 'Rina Wulandari',
            'username' => 'rina_wulan',
        ];
    }

    // ---------- D1: nama_asli ----------

    public function test_update_profil_tanpa_nama_asli_ditolak_validasi(): void
    {
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)
            ->put('/extras/profil', ['alias' => 'Alias Baru', 'username' => 'rina_wulan'])
            ->assertSessionHasErrors('nama_asli');

        $this->assertNull($extras->fresh()->nama_asli);
    }

    public function test_update_profil_dengan_nama_asli_tersimpan(): void
    {
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)
            ->put('/extras/profil', $this->payloadProfil())
            ->assertRedirect();

        $this->assertSame('Rina Wulandari', $extras->fresh()->nama_asli);
    }

    public function test_field_existing_tetap_tersimpan_bareng_field_baru(): void
    {
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)
            ->put('/extras/profil', $this->payloadProfil([
                'rate_card' => 300000,
                'usia' => 28,
                'nomor_wa' => '081234567890',
            ]))
            ->assertRedirect();

        $extras->refresh();
        $this->assertSame('Alias Baru', $extras->alias);
        $this->assertEquals(300000, $extras->rate_card);
        $this->assertSame(28, (int) $extras->usia);
        $this->assertSame('6281234567890', $extras->user->fresh()->nomor_wa);
    }

    public function test_form_profil_menampilkan_field_nama_asli(): void
    {
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)
            ->get('/extras/profil/lengkapi')
            ->assertSee('Nama Asli (sesuai KTP)')
            ->assertSee('Dipakai di dokumen kontrak resmi, bukan yang tampil ke publik.');
    }

    // ---------- D3: username ----------

    public function test_username_yang_sudah_dipakai_user_lain_ditolak(): void
    {
        User::factory()->create(['role' => 'extras', 'username' => 'rina_wulan']);
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)
            ->put('/extras/profil', $this->payloadProfil())
            ->assertSessionHasErrors('username');

        $this->assertNull($extras->user->fresh()->username);
    }

    public function test_username_milik_sendiri_boleh_disimpan_ulang(): void
    {
        $extras = $this->buatExtras([], ['username' => 'rina_wulan']);

        $this->actingAs($extras->user)
            ->put('/extras/profil', $this->payloadProfil())
            ->assertSessionHasNoErrors();

        $this->assertSame('rina_wulan', $extras->user->fresh()->username);
    }

    public function test_username_tanpa_kirim_apa_pun_ditolak(): void
    {
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)
            ->put('/extras/profil', ['alias' => 'Alias Baru', 'nama_asli' => 'Rina Wulandari'])
            ->assertSessionHasErrors('username');
    }

    public function test_username_dengan_spasi_atau_karakter_aneh_ditolak(): void
    {
        $extras = $this->buatExtras();

        foreach (['rina wulan', 'rina@wulan', 'rina.wulan'] as $username) {
            $this->actingAs($extras->user)
                ->put('/extras/profil', $this->payloadProfil(['username' => $username]))
                ->assertSessionHasErrors('username');
        }

        $this->assertNull($extras->user->fresh()->username);
    }

    public function test_username_disimpan_di_users_bukan_extras_profiles(): void
    {
        $extras = $this->buatExtras();

        $this->actingAs($extras->user)->put('/extras/profil', $this->payloadProfil())->assertRedirect();

        $this->assertSame('rina_wulan', $extras->user->fresh()->username);
        $this->assertArrayNotHasKey('username', $extras->fresh()->getAttributes());
    }

    // ---------- D3: tampilan "Alias (@username)" ----------

    public function test_alias_sama_username_beda_tampil_berbeda_di_halaman_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProject($admin);

        foreach (['rina_a', 'rina_b'] as $username) {
            $extras = $this->buatExtras(['alias' => 'Rina'], ['username' => $username]);
            ProjectApplication::create([
                'casting_project_id' => $project->id,
                'extras_id' => $extras->id,
                'status_partisipasi' => 'diajukan',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.projects.applicants', $project));

        $response->assertOk()
            ->assertSee('Rina (@rina_a)')
            ->assertSee('Rina (@rina_b)');
    }

    public function test_extras_tanpa_username_tampil_tanpa_kurung_kosong(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProject($admin);
        $extras = $this->buatExtras(['alias' => 'Rina']);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.projects.applicants', $project));

        $response->assertOk()
            ->assertSee('Rina')
            ->assertDontSee('(@)');

        $this->assertSame('Rina', $extras->fresh()->alias_tampil);
    }

    public function test_alias_tampil_gabungkan_alias_dan_username(): void
    {
        $extras = $this->buatExtras(['alias' => 'Rina'], ['username' => 'rina_wulan']);

        $this->assertSame('Rina (@rina_wulan)', $extras->fresh()->alias_tampil);
    }

    // ---------- D2: gate & PDF kontrak ----------

    public function test_akses_kontrak_tanpa_nama_asli_redirect_ke_profil_dan_kontrak_tidak_dibuat(): void
    {
        $application = $this->buatApplicationLolos(namaAsli: null);

        $response = $this->actingAs($application->extras->user)
            ->get(route('contracts.show', $application));

        $response->assertRedirect(route('extras.profile.edit'));
        $this->assertSame(0, Contract::count());
    }

    public function test_admin_akses_kontrak_tanpa_nama_asli_extras_dapat_pesan_error(): void
    {
        $application = $this->buatApplicationLolos(namaAsli: null);
        $admin = $application->castingProject->admin;

        $response = $this->actingAs($admin)->get(route('contracts.show', $application));

        $response->assertRedirect(route('admin.projects.applicants', $application->castingProject))
            ->assertSessionHas('error');
        $this->assertSame(0, Contract::count());
    }

    public function test_nama_asli_terisi_tapi_nik_kosong_tetap_kena_gate_nik(): void
    {
        $application = $this->buatApplicationLolos();

        $this->actingAs($application->extras->user)
            ->get(route('contracts.show', $application))
            ->assertRedirect(route('extras.kontrak.lengkapi-ktp', $application));

        $this->assertSame(0, Contract::count());
    }

    public function test_kontrak_digenerate_dan_pdf_pakai_nama_asli_bukan_alias(): void
    {
        Mail::fake();
        Storage::fake('local');

        $application = $this->buatApplicationLolos();
        $application->extras->lengkapiKtp('3201234567890011', 'BCA 111');

        $this->actingAs($application->extras->user)
            ->get(route('contracts.show', $application))
            ->assertOk();

        $this->assertDatabaseCount('contracts', 1);

        $html = view('contracts.pdf-template', [
            'application' => $application->fresh()->load('contract', 'extras', 'castingProject'),
        ])->render();

        $this->assertStringContainsString('Rina Wulandari', $html);
        $this->assertStringContainsString('Nama Talent (sesuai KTP)', $html);
        // Nama penandatangan di kolom Pihak Talent = nama KTP, bukan alias.
        $this->assertStringContainsString('<div class="signature-line">Rina Wulandari</div>', $html);
        $this->assertStringNotContainsString('<div class="signature-line">Alias Lama</div>', $html);
    }

    private function buatProject(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function buatApplicationLolos(?string $namaAsli = 'Rina Wulandari'): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extras = $this->buatExtras($namaAsli ? ['nama_asli' => $namaAsli] : []);

        return ProjectApplication::create([
            'casting_project_id' => $this->buatProject($admin)->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Diagnosa: proyek "Iklan Mahasiswa Layar Senja" (id 16) dilaporkan tidak
 * muncul di /extras/lowongan padahal Admin melihat "Lowongan: Dibuka".
 * Test ini mereproduksi alur store() Admin persis seperti yang dilaporkan.
 */
class LowonganTerbukaDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    public function test_proyek_dari_form_admin_dengan_input_dilaporkan_muncul_di_lowongan_terbuka(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($admin)->post(route('admin.projects.store'), [
            'nama_produksi' => 'Iklan Mahasiswa Layar Senja',
            'client_id' => $client->id,
            'deadline' => '2026-11-20',
            'kuota' => 3,
            'tanggal_shooting' => ['2026-11-25'],
            'kelas' => [
                [
                    'nama_kelas' => 'Mahasiswa',
                    'budget_client' => 500000,
                    'kuota_kelas' => 3,
                ],
            ],
        ]);

        $response->assertRedirect();

        $project = CastingProject::where('nama_produksi', 'Iklan Mahasiswa Layar Senja')->first();
        $this->assertNotNull($project, 'Proyek tidak tersimpan sama sekali.');

        $project->load('classes');
        $this->assertSame('dibuka', $project->status);
        $this->assertSame('disetujui', $project->client_request_status);
        $this->assertCount(1, $project->classes);
        $this->assertSame(3, $project->classes->first()->kuota_kelas);
        $this->assertEquals(500000, $project->classes->first()->budget_client);

        $this->assertFalse($project->kuotaPenuh(), 'kuotaPenuh() seharusnya false untuk kelas baru dengan kuota 3.');
        $this->assertTrue($project->menerimaPendaftaran(), 'menerimaPendaftaran() seharusnya true untuk proyek baru dibuka.');

        $terbuka = CastingProject::lowonganTerbuka();
        $this->assertTrue(
            $terbuka->contains('id', $project->id),
            'Proyek dari input yang dilaporkan seharusnya muncul di lowonganTerbuka(), tapi tidak.'
        );

        // Extras benar-benar melihatnya di halaman /extras/lowongan.
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $this->actingAs($extrasUser)->get(route('extras.projects.index'))
            ->assertOk()
            ->assertSee('Iklan Mahasiswa Layar Senja');
    }

    /**
     * BUG QA: kartu lowongan Extras berlabel "Sisa Kuota Peran" tapi nilainya "terisi/kuota"
     * (project-level), kontradiksi dengan baris per-peran "Sisa X dari Y" (sisaKuota()/kuota_kelas).
     * Kartu sekarang pakai definisi sama: jumlah sisaKuota() semua kelas / jumlah kuota_kelas.
     */
    public function test_kartu_lowongan_tidak_kontradiksi_dengan_baris_sisa_per_peran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'client_id' => $client->id,
            'nama_produksi' => 'Proyek Kuota Konsisten',
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
            'deadline' => today()->addDays(5),
            'kuota' => 99,
        ]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Figuran A', 'kuota_kelas' => 3]);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $kelas->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'status_partisipasi' => 'lolos',
        ]);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $this->actingAs($extrasUser)->get(route('extras.projects.index'))
            ->assertOk()
            ->assertSee('2/3 tersisa')
            ->assertSee('Sisa 2 dari 3');
    }
}

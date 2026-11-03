<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ExtractsPdfText;
use Tests\TestCase;

/** Audit item 6 (CJ.1): nama Client/PH tidak boleh tampil ke Extras kecuali tampilkan_nama_client aktif. */
class CjSembunyikanNamaClientDariExtrasTest extends TestCase
{
    use ExtractsPdfText, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function client(): User
    {
        return User::factory()->create(['role' => 'client', 'nama_perusahaan' => 'PT Rahasia Corp']);
    }

    private function proyek(User $client, bool $tampilkan): CastingProject
    {
        return CastingProject::factory()->create([
            'client_id' => $client->id,
            'nama_produksi' => 'Iklan Sabun Wangi',
            'tampilkan_nama_client' => $tampilkan,
        ]);
    }

    private function extras(): User
    {
        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_extras_bukan_peserta_tidak_lihat_nama_client_di_katalog_dan_detail(): void
    {
        $project = $this->proyek($this->client(), false);
        $extras = $this->extras();

        $this->actingAs($extras)->get(route('extras.projects.index'))
            ->assertSee('Iklan Sabun Wangi')
            ->assertDontSee('PT Rahasia Corp');

        $this->actingAs($extras)->get(route('extras.projects.show', $project))
            ->assertOk()
            ->assertDontSee('PT Rahasia Corp');
    }

    public function test_extras_peserta_tidak_lihat_nama_client_meski_sudah_kontrak(): void
    {
        $project = $this->proyek($this->client(), false);
        $extrasUser = $this->extras();
        $extras = ExtrasProfile::where('user_id', $extrasUser->id)->first();
        $extras->update(['nama_asli' => 'Dimas R', 'nik' => '3170000000000001']);
        $kelas = $project->classes()->create(['nama_kelas' => 'Warga', 'kuota_kelas' => 2]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'casting_project_class_id' => $kelas->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
            'fee_final' => 200000,
        ]);
        $application->contract()->create([]);

        $this->actingAs($extrasUser)->get(route('extras.projects.show', $project))
            ->assertOk()->assertDontSee('PT Rahasia Corp');

        $pdf = $this->actingAs($extrasUser)->get(route('contracts.download-pdf', $application));
        $pdf->assertOk();
        $this->assertStringNotContainsString('PT Rahasia Corp', $this->teksPdf($pdf->streamedContent()));
    }

    public function test_search_lowongan_tidak_lagi_cocok_dengan_nama_client_tapi_tetap_cocok_nama_produksi(): void
    {
        $project = $this->proyek($this->client(), false);
        $extras = $this->extras();

        $html = $this->actingAs($extras)->get(route('extras.projects.index'))->getContent();
        preg_match('/data-search="([^"]*)"/', $html, $m);

        $this->assertStringContainsString('iklan sabun wangi', $m[1]);
        $this->assertStringNotContainsString('rahasia', $m[1]);
    }

    public function test_tampilkan_nama_client_true_extras_tetap_bisa_lihat(): void
    {
        $project = $this->proyek($this->client(), true);
        $extras = $this->extras();

        $this->actingAs($extras)->get(route('extras.projects.index'))
            ->assertSee('PT Rahasia Corp');
        $this->actingAs($extras)->get(route('extras.projects.show', $project))
            ->assertSee('PT Rahasia Corp');
    }
}

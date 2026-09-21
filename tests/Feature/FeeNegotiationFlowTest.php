<?php

namespace Tests\Feature;

use App\Exports\CdRiwayatExport;
use App\Models\CastingProject;
use App\Models\CdReview;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-16 s.d. RF-21: negosiasi fee in-app ala InDrive (multi-round, tercatat).
 * Ini "value inti" produk (docs/CLAUDE.md: "catatan kesepakatan fee yang
 * nggak bisa dibantah") - sebelumnya cuma dites dari sisi "email terkirim"
 * (EmailNotificationTest), belum pernah dites logic negosiasinya sendiri
 * (round bertambah benar, Deal mengunci fee, tolak/deal memblokir aksi
 * lanjutan, ajukanKeCd() menjaga urutan). Model FeeNegotiation sendiri
 * py catatan: bug MassAssignmentException di 4 method ini pernah lolos
 * lama karena "belum ada testing end-to-end" - file ini nutup gap itu.
 */
class FeeNegotiationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(string $status = 'direview_admin'): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Nego', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ]);
    }

    public function test_ajukan_fee_awal_membuat_round_1_dan_ubah_status_ke_nego_fee(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('nego_fee', $application->status_partisipasi);
        $this->assertSame(1, $application->feeNegotiations()->count());
        $negotiation = $application->feeNegotiations()->first();
        $this->assertSame(1, $negotiation->round);
        $this->assertSame('admin', $negotiation->diajukan_oleh);
        $this->assertSame('tawar', $negotiation->aksi);
        $this->assertEquals(200000, $negotiation->nominal);
    }

    public function test_ajukan_fee_awal_kedua_kali_ditolak_tidak_bikin_round_baru(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 999999]);

        $this->assertSame(1, $application->feeNegotiations()->count());
    }

    public function test_multi_round_counter_bergantian_tidak_dibatasi_jumlah_putaran(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($extras)->post(route('extras.negotiations.counter', $application), ['nominal' => 300000]);
        $this->actingAs($admin)->post(route('admin.negotiations.counter', $application), ['nominal' => 250000]);
        $this->actingAs($extras)->post(route('extras.negotiations.counter', $application), ['nominal' => 275000]);

        $rounds = $application->feeNegotiations()->orderBy('round')->get();
        $this->assertCount(4, $rounds);
        $this->assertSame([1, 2, 3, 4], $rounds->pluck('round')->all());
        $this->assertSame(['admin', 'extras', 'admin', 'extras'], $rounds->pluck('diajukan_oleh')->all());
        $this->assertEquals([200000, 300000, 250000, 275000], $rounds->pluck('nominal')->map(fn ($n) => (float) $n)->all());
    }

    public function test_extras_terima_mengunci_fee_final_dan_set_status_deal(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($extras)->post(route('extras.negotiations.terima', $application), ['nominal' => 200000])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('deal', $application->status_partisipasi);
        $this->assertEquals(200000, $application->fee_final);
    }

    public function test_admin_terima_juga_bisa_mengunci_deal(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($extras)->post(route('extras.negotiations.counter', $application), ['nominal' => 250000]);
        $this->actingAs($admin)->post(route('admin.negotiations.terima', $application), ['nominal' => 250000])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('deal', $application->status_partisipasi);
        $this->assertEquals(250000, $application->fee_final);
    }

    public function test_setelah_deal_nego_lanjutan_ditolak_422(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($extras)->post(route('extras.negotiations.terima', $application), ['nominal' => 200000]);

        $this->actingAs($admin)->post(route('admin.negotiations.counter', $application), ['nominal' => 300000])
            ->assertStatus(422);
        $this->actingAs($extras)->post(route('extras.negotiations.counter', $application), ['nominal' => 300000])
            ->assertStatus(422);

        $this->assertSame(2, $application->feeNegotiations()->count());
    }

    public function test_admin_tolak_negosiasi_set_status_ditolak_dan_blokir_lanjutan(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($admin)->post(route('admin.negotiations.tolak', $application))->assertRedirect();

        $application->refresh();
        $this->assertSame('ditolak', $application->status_partisipasi);

        $this->actingAs($extras)->post(route('extras.negotiations.counter', $application), ['nominal' => 999999])
            ->assertStatus(422);
    }

    public function test_ajukan_ke_cd_gagal_kalau_belum_deal(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($admin)->post(route('admin.negotiations.ajukan-ke-cd', $application))->assertRedirect();

        $this->assertSame('nego_fee', $application->fresh()->status_partisipasi);
    }

    public function test_ajukan_ke_cd_berhasil_setelah_deal(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;
        $cd = User::factory()->create(['role' => 'casting_director']);
        $application->castingProject->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($extras)->post(route('extras.negotiations.terima', $application), ['nominal' => 200000]);
        $this->actingAs($admin)->post(route('admin.negotiations.ajukan-ke-cd', $application))->assertRedirect();

        $this->assertSame('diajukan_ke_cd', $application->fresh()->status_partisipasi);
    }

    public function test_extras_lain_tidak_bisa_akses_negosiasi_milik_extras_lain(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);

        $extrasLain = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasLain->id]);

        $this->actingAs($extrasLain)->get(route('extras.negotiations.show', $application))->assertForbidden();
        $this->actingAs($extrasLain)->post(route('extras.negotiations.terima', $application), ['nominal' => 200000])
            ->assertForbidden();
        $this->actingAs($extrasLain)->post(route('extras.negotiations.counter', $application), ['nominal' => 1])
            ->assertForbidden();
    }

    public static function bukanAdminDefaultProvider(): array
    {
        return [
            ['korlap'], ['client'], ['extras'],
        ];
    }

    #[DataProvider('bukanAdminDefaultProvider')]
    public function test_role_selain_admin_default_ditolak_di_route_negosiasi_admin(string $role): void
    {
        $application = $this->buatAplikasi('direview_admin');
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000])
            ->assertForbidden();
    }

    public function test_export_riwayat_scoped_per_cd(): void
    {
        Excel::fake();

        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $cdB = User::factory()->create(['role' => 'casting_director']);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Export', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $app = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);

        CdReview::create(['cd_id' => $cdA->id, 'project_application_id' => $app->id, 'keputusan' => 'approve']);
        CdReview::create(['cd_id' => $cdB->id, 'project_application_id' => $app->id, 'keputusan' => 'reject']);

        $this->actingAs($cdA)->get(route('cd.riwayat.export.xlsx', $project))->assertOk();
        Excel::assertDownloaded('riwayat-proyek-export.xlsx');

        $exportA = new CdRiwayatExport($cdA->id, $project->id);
        $exportB = new CdRiwayatExport($cdB->id, $project->id);

        $this->assertCount(1, $exportA->collection());
        $this->assertCount(1, $exportB->collection());
        $this->assertSame('Approve', $exportA->collection()->first()['keputusan']);
        $this->assertSame('Reject', $exportB->collection()->first()['keputusan']);
    }

    public function test_riwayat_level1_hanya_proyek_milik_cd(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);

        $projectA = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Ada Review', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $projectB = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Tanpa Review', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        // Level 1 sekarang berbasis CD assignment, bukan CdReview.
        // CD hanya di-assign ke projectA - projectB tidak muncul.
        $projectA->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $response = $this->actingAs($cd)->get(route('cd.reviews.index'));
        $response->assertOk()
            ->assertSee('Proyek Ada Review')
            ->assertDontSee('Proyek Tanpa Review');
    }

    public function test_riwayat_level2_tidak_expose_data_terlarang(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Level2', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        // show() sekarang cek CD assignment
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $extrasUser = User::factory()->create(['role' => 'extras', 'username' => 'panggilan_user']);
        $extras = ExtrasProfile::create([
            'user_id' => $extrasUser->id,
            'nama_asli' => 'Nama Asli Rahasia',
        ]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 100000,
        ]);

        $response = $this->actingAs($cd)->get(route('cd.reviews.show', $project));
        $response->assertOk()
            ->assertDontSee('Nama Asli Rahasia')
            ->assertDontSee('rate_card')
            ->assertSee('panggilan_user');
    }

    public function test_export_pdf_riwayat(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek PDF', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $app = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 100000,
        ]);

        CdReview::create(['cd_id' => $cd->id, 'project_application_id' => $app->id, 'keputusan' => 'approve']);

        $response = $this->actingAs($cd)->get(route('cd.riwayat.export.pdf', $project));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}

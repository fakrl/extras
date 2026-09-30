<?php

namespace Tests\Feature;

use App\Exports\ClientRiwayatExport;
use App\Models\CastingProject;
use App\Models\ClientReview;
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
 * lanjutan, ajukanKeClient() menjaga urutan). Model FeeNegotiation sendiri
 * py catatan: bug MassAssignmentException di 4 method ini pernah lolos
 * lama karena "belum ada testing end-to-end" - file ini nutup gap itu.
 */
class FeeNegotiationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(string $status = 'direview_admin'): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Nego',
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
        $this->actingAs($admin)->post(route('admin.negotiations.tolak', $application))->assertSessionHasErrors('alasan');
        $this->actingAs($admin)->post(route('admin.negotiations.tolak', $application), ['alasan' => 'Tidak sesuai kriteria'])->assertRedirect();

        $application->refresh();
        $this->assertSame('ditolak', $application->status_partisipasi);
        $this->assertSame('Tidak sesuai kriteria', $application->feeNegotiations()->where('aksi', 'tolak')->first()->catatan);

        $this->actingAs($extras)->post(route('extras.negotiations.counter', $application), ['nominal' => 999999])
            ->assertStatus(422);
    }

    public function test_ajukan_ke_client_gagal_kalau_belum_deal(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($admin)->post(route('admin.negotiations.ajukan-ke-client', $application))->assertRedirect();

        $this->assertSame('nego_fee', $application->fresh()->status_partisipasi);
    }

    public function test_ajukan_ke_client_berhasil_setelah_deal(): void
    {
        Mail::fake();
        $application = $this->buatAplikasi('direview_admin');
        $admin = $application->castingProject->admin;
        $extras = $application->extras->user;
        $klien = User::factory()->create(['role' => 'client']);
        $application->castingProject->update(['client_id' => $klien->id]);

        $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000]);
        $this->actingAs($extras)->post(route('extras.negotiations.terima', $application), ['nominal' => 200000]);
        $this->actingAs($admin)->post(route('admin.negotiations.ajukan-ke-client', $application))->assertRedirect();

        $this->assertSame('diajukan_ke_client', $application->fresh()->status_partisipasi);
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
    public function test_role_selain_admin_ditolak_di_route_negosiasi_admin(string $role): void
    {
        $application = $this->buatAplikasi('direview_admin');
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->post(route('admin.negotiations.ajukan', $application), ['nominal' => 200000])
            ->assertForbidden();
    }

    public function test_export_riwayat_scoped_per_client(): void
    {
        Excel::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $klienA = User::factory()->create(['role' => 'client']);
        $klienB = User::factory()->create(['role' => 'client']);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Export',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $app = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);

        ClientReview::create(['client_id' => $klienA->id, 'project_application_id' => $app->id, 'keputusan' => 'approve']);
        ClientReview::create(['client_id' => $klienB->id, 'project_application_id' => $app->id, 'keputusan' => 'reject']);

        $this->actingAs($klienA)->get(route('client.riwayat.export.xlsx', $project))->assertForbidden();
        $project->update(['client_id' => $klienA->id]);
        $this->actingAs($klienA)->get(route('client.riwayat.export.xlsx', $project))->assertOk();
        Excel::assertDownloaded('riwayat-proyek-export.xlsx');

        $exportA = new ClientRiwayatExport($klienA->id, $project->id);
        $exportB = new ClientRiwayatExport($klienB->id, $project->id);

        $this->assertCount(1, $exportA->collection());
        $this->assertCount(1, $exportB->collection());
        $this->assertSame('Approve', $exportA->collection()->first()['keputusan']);
        $this->assertSame('Reject', $exportB->collection()->first()['keputusan']);
    }

    public function test_riwayat_level1_hanya_proyek_milik_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);

        $projectA = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Ada Review',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $projectB = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Tanpa Review',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        // Level 1 sekarang berbasis Client assignment, bukan ClientReview.
        // Client hanya di-assign ke projectA - projectB tidak muncul.
        $projectA->update(['client_id' => $klien->id]);

        $response = $this->actingAs($klien)->get(route('client.reviews.index'));
        $response->assertOk()
            ->assertSee('Proyek Ada Review')
            ->assertDontSee('Proyek Tanpa Review');
    }

    public function test_riwayat_level2_tidak_expose_data_terlarang(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Level2',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        // show() sekarang cek Client assignment
        $project->update(['client_id' => $klien->id]);

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

        $response = $this->actingAs($klien)->get(route('client.reviews.show', $project));
        $response->assertOk()
            ->assertDontSee('Nama Asli Rahasia')
            ->assertDontSee('rate_card')
            ->assertSee('panggilan_user');
    }

    public function test_export_pdf_riwayat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek PDF',
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

        ClientReview::create(['client_id' => $klien->id, 'project_application_id' => $app->id, 'keputusan' => 'approve']);

        $this->actingAs($klien)->get(route('client.riwayat.export.pdf', $project))->assertForbidden();
        $project->update(['client_id' => $klien->id]);
        $response = $this->actingAs($klien)->get(route('client.riwayat.export.pdf', $project));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}

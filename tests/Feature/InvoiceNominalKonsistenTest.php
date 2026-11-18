<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNominalKonsistenTest extends TestCase
{
    use RefreshDatabase;

    public function test_nominal_invoice_sama_antara_list_dan_detail_saat_budget_total_beda_dari_alokasi_kelas(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::create([
            'nama_produksi' => 'Series Kampus Biru eps 1-2', 'status' => 'dibuka',
            'client_request_status' => 'disetujui', 'client_id' => $client->id, 'kuota' => 8,
            'budget_total' => 2400000, 'deadline' => today()->subDays(5),
        ]);
        $project->classes()->create(['nama_kelas' => 'Mahasiswa', 'kuota_kelas' => 6, 'budget_client' => 300000]);
        $project->classes()->create(['nama_kelas' => 'Dosen', 'kuota_kelas' => 2, 'budget_client' => 400000]);
        $project->invoices()->create([]);

        $list = $this->actingAs($client)->get(route('invoices.index-client'));
        $show = $this->actingAs($client)->get(route('invoices.show', $project));

        $list->assertOk()->assertSee('Rp 2.400.000', false);
        $show->assertOk()->assertSee('Rp 2.400.000', false);
        $show->assertDontSee('Rp 2.600.000', false);
    }
}

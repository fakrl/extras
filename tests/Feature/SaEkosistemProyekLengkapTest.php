<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoLengkapSeeder;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaEkosistemProyekLengkapTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_ekosistem_proyek_menjumlah_ke_total_dan_kpi_proyek_berjalan_ada_label_periode(): void
    {
        Storage::fake('local');
        Mail::fake();
        Queue::fake();
        Http::fake();
        $this->seed([ExtrasCategorySeeder::class, DemoLengkapSeeder::class]);

        $sa = User::where('role', 'super_admin')->firstOrFail();
        $response = $this->actingAs($sa)->get(route('super-admin.dashboard'));
        $response->assertOk();

        $stat = $response->viewData('statProyekKuota');

        $this->assertSame(
            $stat['total'],
            $stat['menunggu_acc'] + $stat['seleksi'] + $stat['shooting'] + $stat['selesai'] + $stat['batal'] + $stat['perlu_revisi'],
            'Semua kategori kartu Ekosistem Proyek harus menjumlah persis ke total proyek (termasuk perlu_revisi), tanpa proyek yang hilang tak terhitung.'
        );

        // Item 5: KPI "Proyek berjalan" dibatasi periode (CR.2.2) tapi sebelum fix tidak ada
        // keterangan apapun di kartunya, jadi kelihatan kontradiksi dgn angka "Berjalan" di
        // kartu Ekosistem yang itung SEMUA proyek tanpa batas periode.
        $response->assertSee('shooting dalam', false);
    }
}

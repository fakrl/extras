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

class AdminProjectsHitunganTindakanTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): User
    {
        Storage::fake('local');
        Mail::fake();
        Queue::fake();
        Http::fake();
        $this->seed([ExtrasCategorySeeder::class, DemoLengkapSeeder::class]);

        return User::where('username', 'admin_rina')->firstOrFail();
    }

    public function test_badge_tab_semua_samakan_jumlah_dengan_hasil_filter_perlu_tindakan(): void
    {
        $rina = $this->seedDemo();

        $tanpaFilter = $this->actingAs($rina)->get('/admin/projects');
        $tanpaFilter->assertOk();
        $jumlahTahapTanpaFilter = $tanpaFilter->viewData('jumlahTahap')[''];

        $denganFilter = $this->actingAs($rina)->get('/admin/projects?tindakan=1');
        $denganFilter->assertOk();
        $jumlahTahapDenganFilter = $denganFilter->viewData('jumlahTahap')[''];
        $totalPaginator = $denganFilter->viewData('projects')->total();

        // Reproduksi: sebelum fix, jumlahTahap[''] SELALU sama dengan hitungan tanpa
        // filter tindakan, walau toggle tindakan=1 aktif - tidak nyambung ke $projects->total().
        $this->assertSame(
            $totalPaginator,
            $jumlahTahapDenganFilter,
            "Badge tab 'Semua' ({$jumlahTahapDenganFilter}) harus sama dengan jumlah baris hasil filter tindakan=1 ({$totalPaginator})."
        );

        $this->assertGreaterThanOrEqual($jumlahTahapDenganFilter, $jumlahTahapTanpaFilter);
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\Payment;
use App\Models\User;
use App\Support\AdminRingkasan;
use Database\Seeders\DemoLengkapSeeder;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** SPEC BQ.1: satu definisi "perlu ditransfer" dipakai semua tempat. */
class BqPerluDitransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_angka_perlu_ditransfer_sama_di_semua_tempat_untuk_data_demo(): void
    {
        Storage::fake('local');
        Mail::fake();
        Queue::fake();
        Http::fake();
        $this->seed([ExtrasCategorySeeder::class, DemoLengkapSeeder::class]);

        $jumlah = Payment::perluDitransfer()->count();
        $this->assertSame(6, $jumlah);
        $this->assertGreaterThan($jumlah, Payment::where('status', 'belum_dibayar')->count());

        $this->assertSame($jumlah, AdminRingkasan::untuk()['bayar']['jumlah']);
        $selesai = AdminRingkasan::tahapan(maks: 50)['selesai'];
        $this->assertSame($jumlah, $selesai['perlu']);
        $this->assertSame($jumlah, $selesai['daftar']->where('teks', 'Honor belum ditransfer')->count());

        $admin = User::where('username', 'admin_rina')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('ringkasan', fn ($r) => $r['bayar']['jumlah'] === $jumlah)
            ->assertViewHas('tahapan', fn ($t) => $t['selesai']['perlu'] === $jumlah);

        $sa = User::where('username', 'fakrul')->firstOrFail();
        $this->actingAs($sa)->get(route('super-admin.monitoring.admin'))->assertOk()
            ->assertViewHas('ringkasan', fn ($r) => $r['bayar']['jumlah'] === $jumlah)
            ->assertViewHas('tahapan', fn ($t) => $t['selesai']['perlu'] === $jumlah);

        $harap = CastingProject::whereHas('payments', fn ($p) => $p->perluDitransfer())->pluck('id')->sort()->values()->all();
        $this->actingAs($admin)->get(route('admin.projects.index', ['bayar' => 'extras']))->assertOk()
            ->assertViewHas('projects', fn ($p) => collect($p->items())->pluck('id')->sort()->values()->all() === $harap);

        $menunggu = Payment::where('status', 'belum_dibayar')->whereHas('projectApplication', fn ($a) => $a->where('status_partisipasi', 'lolos'))->firstOrFail();
        $this->assertSame('Menunggu kontrak', $menunggu->label());
        $this->assertSame('Belum Dibayar', Payment::perluDitransfer()->first()->label());
    }
}

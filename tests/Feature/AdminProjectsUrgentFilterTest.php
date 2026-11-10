<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProjectsUrgentFilterTest extends TestCase
{
    use RefreshDatabase;

    private function daftar(CastingProject $p, ?int $kelasId, int $jumlah): void
    {
        foreach (range(1, $jumlah) as $i) {
            $p->applications()->create([
                'extras_id' => ExtrasProfile::factory()->create()->id,
                'casting_project_class_id' => $kelasId,
                'status_partisipasi' => 'kontrak_ditandatangani',
            ]);
        }
    }

    public function test_filter_urgent_query_level_konsisten_dengan_is_urgent_model(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // A: ditandai urgent manual, tanpa jadwal shooting - tetap harus masuk.
        $a = CastingProject::factory()->create(['admin_id' => $admin->id, 'is_urgent' => true, 'kuota' => 10]);

        // B: shooting H+2, tanpa kelas, kuota penuh (2/2) - HARUS dikecualikan.
        $b = CastingProject::factory()->create(['admin_id' => $admin->id, 'kuota' => 2]);
        $b->shootingDates()->create(['tanggal' => today()->addDays(2)]);
        $this->daftar($b, null, 2);

        // C: shooting H+2, tanpa kelas, kuota masih terbuka (1/2) - HARUS masuk.
        $c = CastingProject::factory()->create(['admin_id' => $admin->id, 'kuota' => 2]);
        $c->shootingDates()->create(['tanggal' => today()->addDays(2)]);
        $this->daftar($c, null, 1);

        // D: shooting H+10 (di luar window), kuota terbuka - HARUS dikecualikan.
        $d = CastingProject::factory()->create(['admin_id' => $admin->id, 'kuota' => 10]);
        $d->shootingDates()->create(['tanggal' => today()->addDays(10)]);

        // E: shooting H+2, pakai kelas, kelas penuh (2/2) - HARUS dikecualikan.
        $e = CastingProject::factory()->create(['admin_id' => $admin->id, 'kuota' => 10]);
        $e->shootingDates()->create(['tanggal' => today()->addDays(2)]);
        $kelasE = $e->classes()->create(['nama_kelas' => 'Warga', 'kuota_kelas' => 2]);
        $this->daftar($e, $kelasE->id, 2);

        // F: shooting H+2, pakai kelas, kelas masih terbuka (1/2) - HARUS masuk.
        $f = CastingProject::factory()->create(['admin_id' => $admin->id, 'kuota' => 10]);
        $f->shootingDates()->create(['tanggal' => today()->addDays(2)]);
        $kelasF = $f->classes()->create(['nama_kelas' => 'Warga', 'kuota_kelas' => 2]);
        $this->daftar($f, $kelasF->id, 1);

        $semua = CastingProject::with('shootingDates', 'classes.applications')->get();
        $expected = $semua->filter(fn ($p) => $p->isUrgent())->pluck('id')->sort()->values();

        $queryUrgentIds = CastingProject::urgent()->pluck('id')->sort()->values();

        $this->assertSame($expected->all(), $queryUrgentIds->all());
        $this->assertTrue($queryUrgentIds->contains($a->id));
        $this->assertTrue($queryUrgentIds->contains($c->id));
        $this->assertTrue($queryUrgentIds->contains($f->id));
        $this->assertFalse($queryUrgentIds->contains($b->id));
        $this->assertFalse($queryUrgentIds->contains($d->id));
        $this->assertFalse($queryUrgentIds->contains($e->id));

        $this->actingAs($admin)->get(route('admin.projects.index', ['urgent' => 1, 'semua' => 1]))
            ->assertOk()
            ->assertSee($a->nama_produksi)
            ->assertSee($c->nama_produksi)
            ->assertSee($f->nama_produksi)
            ->assertDontSee($b->nama_produksi)
            ->assertDontSee($d->nama_produksi)
            ->assertDontSee($e->nama_produksi);
    }
}

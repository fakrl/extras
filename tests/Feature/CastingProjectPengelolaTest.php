<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CastingProjectPengelolaTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bisa_update_pic_korlap_dan_client_lewat_pengelola_endpoint(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Budi']);
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Joko']);
        $client = User::factory()->create(['role' => 'client', 'name' => 'Client Rudi', 'nama_perusahaan' => 'PH Sukses']);

        $project = CastingProject::factory()->create([
            'admin_id' => null,
            'client_id' => null,
            'nama_produksi' => 'Iklan Kopi Segar',
        ]);

        $response = $this->actingAs($superAdmin)->patchJson(route('admin.projects.pengelola', $project), [
            'admin_id' => $admin->id,
            'korlap_id' => $korlap->id,
            'client_id' => $client->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'client_name' => 'PH Sukses',
                'pic_name' => 'Admin Budi',
                'korlap_name' => 'Korlap Joko',
            ]);

        $project->refresh();
        $this->assertSame($admin->id, $project->admin_id);
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame($korlap->id, $project->korlap()?->id);

        $this->assertTrue(ActivityLog::where('action', 'UPDATE_PROJECT_PIC')
            ->where('description', 'like', '%Iklan Kopi Segar%')
            ->exists());
    }

    public function test_admin_biasa_bisa_update_pengelola_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Utama']);
        $admin2 = User::factory()->create(['role' => 'admin', 'name' => 'Admin Kedua']);
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Lapangan']);

        $project = CastingProject::factory()->create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Film Layar Lebar',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.projects.pengelola', $project), [
            'admin_id' => $admin2->id,
            'korlap_id' => $korlap->id,
        ]);

        $response->assertRedirect();
        $project->refresh();
        $this->assertSame($admin2->id, $project->admin_id);
        $this->assertSame($korlap->id, $project->korlap()?->id);
    }

    public function test_extras_dan_client_dilarang_akses_update_pengelola(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create();

        $this->actingAs($extras)->patch(route('admin.projects.pengelola', $project), [])->assertForbidden();
        $this->actingAs($client)->patch(route('admin.projects.pengelola', $project), [])->assertForbidden();
    }
}

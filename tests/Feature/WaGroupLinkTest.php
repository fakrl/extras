<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaGroupLinkTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7)->toDateString(),
            'kuota' => 5,
            'tanggal_shooting' => [now()->addDays(10)->toDateString()],
            'kelas' => [
                ['nama_kelas' => 'Ibu-ibu', 'budget_client' => 400000, 'kuota_kelas' => 3],
            ],
        ], $overrides);
    }

    public function test_wa_group_link_tersimpan_dan_tampil(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $response = $this->actingAs($admin)->post('/admin/projects', $this->payload([
            'wa_group_link' => 'https://chat.whatsapp.com/abc123',
        ]));

        $response->assertRedirect(route('admin.projects.index'));

        $project = CastingProject::first();
        $this->assertSame('https://chat.whatsapp.com/abc123', $project->wa_group_link);

        $this->actingAs($admin)->get('/admin/projects')
            ->assertOk()
            ->assertSee('https://chat.whatsapp.com/abc123');

        $this->actingAs($admin)->get(route('admin.projects.applicants', $project))
            ->assertOk()
            ->assertSee('https://chat.whatsapp.com/abc123');
    }

    public function test_create_proyek_tanpa_link_tetap_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $response = $this->actingAs($admin)->post('/admin/projects', $this->payload());

        $response->assertRedirect(route('admin.projects.index'));
        $this->assertNull(CastingProject::first()->wa_group_link);
    }

    public function test_wa_group_link_invalid_url_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $response = $this->actingAs($admin)->post('/admin/projects', $this->payload([
            'wa_group_link' => 'bukan-url',
        ]));

        $response->assertSessionHasErrors('wa_group_link');
    }
}

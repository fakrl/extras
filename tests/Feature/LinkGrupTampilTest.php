<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkGrupTampilTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Test',
            'client_id' => User::factory()->create(['role' => 'client'])->id,
            'deadline' => now()->addDays(7)->toDateString(),
            'kuota' => 5,
            'tanggal_shooting' => [now()->addDays(10)->toDateString()],
            'kelas' => [
                ['nama_kelas' => 'Ibu-ibu', 'budget_client' => 400000, 'kuota_kelas' => 3],
            ],
        ], $overrides);
    }

    public function test_link_grup_tersimpan_dan_tampil_di_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/projects', $this->payload([
            'link_grup' => 'https://chat.whatsapp.com/abc123',
        ]));

        $response->assertRedirect(route('admin.projects.index'));

        $project = CastingProject::first();
        $this->assertSame('https://chat.whatsapp.com/abc123', $project->link_grup);

        $this->actingAs($admin)->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('https://chat.whatsapp.com/abc123');

        $this->actingAs($admin)->get(route('admin.projects.applicants', $project))
            ->assertOk()
            ->assertSee('https://chat.whatsapp.com/abc123');
    }

    public function test_create_proyek_tanpa_link_tetap_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/projects', $this->payload());

        $response->assertRedirect(route('admin.projects.index'));
        $this->assertNull(CastingProject::first()->link_grup);
    }

    public function test_link_grup_invalid_url_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/projects', $this->payload([
            'link_grup' => 'bukan-url',
        ]));

        $response->assertSessionHasErrors('link_grup');
    }
}

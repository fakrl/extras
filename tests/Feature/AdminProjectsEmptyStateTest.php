<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProjectsEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tanpa_proyek_dan_tanpa_filter_lihat_pesan_khusus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Belum ada proyek yang kamu kelola. Proyek yang sebelumnya kamu pegang mungkin sudah dialihkan.')
            ->assertDontSee('Belum ada proyek casting. Klik');
    }

    public function test_super_admin_tanpa_proyek_tetap_lihat_pesan_lama(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Belum ada proyek casting. Klik')
            ->assertDontSee('Belum ada proyek yang kamu kelola');
    }

    public function test_admin_dengan_filter_aktif_tetap_lihat_pesan_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.projects.index', ['q' => 'tidak-ada-yang-cocok']))
            ->assertOk()
            ->assertSee('Tidak ada proyek yang sesuai filter.')
            ->assertDontSee('Belum ada proyek yang kamu kelola')
            ->assertDontSee('Belum ada proyek casting. Klik');
    }
}

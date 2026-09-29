<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\CdProjectAssignment;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ManajemenAkunTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sa = User::factory()->create(['role' => 'super_admin']);
    }

    private function akun(array $query = [])
    {
        return $this->actingAs($this->sa)->get(route('super-admin.akun.index', $query))->assertOk();
    }

    private function extras(string $nama, array $profil = []): ExtrasProfile
    {
        return ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras', 'name' => $nama])->id] + $profil);
    }

    public function test_search_nama_username_email_wa(): void
    {
        User::factory()->create(['role' => 'admin', 'name' => 'Budi Santoso', 'username' => 'budi_s', 'email' => 'budi@x.test', 'nomor_wa' => '0812 1111 2222']);
        User::factory()->create(['role' => 'korlap', 'name' => 'Siti Rahma', 'username' => 'siti_r', 'email' => 'siti@x.test']);

        foreach (['Budi', 'budi_s', 'budi@x.test', '081211112222'] as $q) {
            $this->akun(['q' => $q])->assertSee('Budi Santoso')->assertDontSee('Siti Rahma');
        }
    }

    public function test_filter_role_dan_status_dihapus(): void
    {
        User::factory()->create(['role' => 'client', 'name' => 'Client Aktif']);
        User::factory()->create(['role' => 'admin', 'name' => 'Admin Aktif']);
        User::factory()->create(['role' => 'admin', 'name' => 'Admin Terhapus'])->delete();

        $this->akun(['role' => 'client'])->assertSee('Client Aktif')->assertDontSee('Admin Aktif');
        $this->akun()->assertSee('Admin Aktif')->assertDontSee('Admin Terhapus');
        $this->akun(['status' => 'dihapus'])->assertSee('Admin Terhapus')->assertDontSee('Admin Aktif')
            ->assertSee(route('super-admin.admins.restore', User::onlyTrashed()->first()->id));
    }

    public function test_filter_sedang_aktif_di_proyek(): void
    {
        $proyek = CastingProject::factory()->create(['client_request_status' => 'disetujui']);
        $proyek->shootingDates()->create(['tanggal' => now()->addDays(5)]);

        ProjectApplication::create(['casting_project_id' => $proyek->id, 'extras_id' => $this->extras('Extras Deal')->id, 'status_partisipasi' => 'deal']);
        ProjectApplication::create(['casting_project_id' => $proyek->id, 'extras_id' => $this->extras('Extras Ditolak')->id, 'status_partisipasi' => 'ditolak']);

        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Jalan']);
        AdminProjectAssignment::create(['casting_project_id' => $proyek->id, 'user_id' => $korlap->id, 'status_log' => 'berjalan', 'assigned_by' => $this->sa->id]);
        $korlapSelesai = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Beres']);
        AdminProjectAssignment::create(['casting_project_id' => $proyek->id, 'user_id' => $korlapSelesai->id, 'status_log' => 'selesai', 'assigned_by' => $this->sa->id]);

        $client = User::factory()->create(['role' => 'client', 'name' => 'Client Mendatang']);
        CdProjectAssignment::create(['casting_project_id' => $proyek->id, 'cd_user_id' => $client->id]);
        User::factory()->create(['role' => 'client', 'name' => 'Client Nganggur']);

        $this->akun(['sedang_aktif' => 1])
            ->assertSee('Extras Deal')->assertSee('Korlap Jalan')->assertSee('Client Mendatang')
            ->assertDontSee('Extras Ditolak')->assertDontSee('Korlap Beres')->assertDontSee('Client Nganggur');
    }

    public function test_filter_tag_multi_dan_grade(): void
    {
        $t1 = ExtrasCategory::firstOrCreate(['nama' => 'Tag Uji Satu']);
        $t2 = ExtrasCategory::firstOrCreate(['nama' => 'Tag Uji Dua']);
        $this->extras('Extras Dua Tag', ['grade_saat_ini' => 'A'])->categories()->sync([$t1->id, $t2->id]);
        $this->extras('Extras Satu Tag', ['grade_saat_ini' => 'B'])->categories()->sync([$t1->id]);
        $this->extras('Extras Tanpa Grade');

        $this->akun(['tag' => [$t1->id]])->assertSee('Extras Dua Tag')->assertSee('Extras Satu Tag')->assertDontSee('Extras Tanpa Grade');
        $this->akun(['tag' => [$t1->id, $t2->id]])->assertSee('Extras Dua Tag')->assertDontSee('Extras Satu Tag')
            ->assertSee('Menampilkan yang punya <strong>semua</strong> tag terpilih', false);
        $this->akun(['grade' => 'B'])->assertSee('Extras Satu Tag')->assertDontSee('Extras Dua Tag');
        $this->akun(['grade' => 'belum'])->assertSee('Extras Tanpa Grade')->assertDontSee('Extras Satu Tag');
    }

    public function test_role_extras_pakai_kartu_dan_aksi_tetap_ada(): void
    {
        $profil = $this->extras('Extras Kartu');
        $profil->user->update(['username' => 'kartu_x']);

        $this->akun(['role' => 'extras'])
            ->assertSee('class="xcard', false)
            ->assertSee('@kartu_x')
            ->assertSee('kelola-'.$profil->user_id, false)
            ->assertSee(route('super-admin.admins.reset-password', $profil->user_id))
            ->assertSee(route('super-admin.admins.toggle-status', $profil->user_id));
    }

    public function test_baris_menampilkan_status_sejak_dan_aktivitas_terakhir(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Log']);
        ActivityLog::create(['user_id' => $admin->id, 'role' => 'admin', 'action' => 'X', 'description' => 'Log lama', 'created_at' => now()->subDay()]);
        ActivityLog::create(['user_id' => $admin->id, 'role' => 'admin', 'action' => 'X', 'description' => 'Log paling baru', 'created_at' => now()]);

        $this->akun()->assertSee('aktif sejak')->assertSee('Log paling baru')->assertDontSee('Log lama')
            ->assertSee('client-baru-dialog', false)->assertSee('add-admin-dialog', false);
    }

    public function test_tidak_ada_n_plus_1_untuk_30_akun(): void
    {
        $tag = ExtrasCategory::firstOrCreate(['nama' => 'Tag Uji N1']);
        foreach (range(1, 15) as $i) {
            $admin = User::factory()->create(['role' => 'admin']);
            ActivityLog::create(['user_id' => $admin->id, 'role' => 'admin', 'action' => 'X', 'description' => 'd', 'created_at' => now()]);
            $this->extras("E{$i}")->categories()->sync([$tag->id]);
        }

        DB::enableQueryLog();
        $this->akun();
        $this->assertLessThan(25, count(DB::getQueryLog()));

        DB::flushQueryLog();
        $this->akun(['role' => 'extras']);
        $this->assertLessThan(25, count(DB::getQueryLog()));
    }

    public function test_route_lama_redirect_ke_akun(): void
    {
        $this->actingAs($this->sa)->get(route('super-admin.monitoring'))->assertRedirect(route('super-admin.akun.index'));
        $this->actingAs($this->sa)->get(route('super-admin.monitoring', ['type' => 'extras', 'q' => 'andi']))
            ->assertRedirect(route('super-admin.akun.index', ['q' => 'andi', 'role' => 'extras']));
        $this->actingAs($this->sa)->get(route('super-admin.admins.index', ['role' => 'client']))
            ->assertRedirect(route('super-admin.akun.index', ['role' => 'client']));
        $this->actingAs($this->sa)->get(route('super-admin.admins.index', ['role' => 'all', 'status' => 'all', 'search' => 'budi']))
            ->assertRedirect(route('super-admin.akun.index', ['q' => 'budi']));
        $this->actingAs($this->sa)->get('/super-admin/casting-directors')->assertRedirect('/super-admin/akun?role=client');
    }

    public function test_reset_dan_toggle_dari_halaman_akun(): void
    {
        $target = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $dari = route('super-admin.akun.index', ['role' => 'korlap']);

        $this->akun(['role' => 'korlap'])
            ->assertSee('action="'.route('super-admin.admins.reset-password', $target).'"', false)
            ->assertSee('action="'.route('super-admin.admins.toggle-status', $target).'"', false);

        $this->actingAs($this->sa)->from($dari)->post(route('super-admin.admins.reset-password', $target))
            ->assertRedirect($dari)->assertSessionHas('kredensial');
        $this->assertTrue($target->fresh()->wajib_ganti_password);

        $this->actingAs($this->sa)->from($dari)->patch(route('super-admin.admins.toggle-status', $target))->assertRedirect($dari);
        $this->assertSame('nonaktif', $target->fresh()->status);
    }

    public function test_detail_akun_ada_profil_riwayat_aktivitas_aksi(): void
    {
        $profil = $this->extras('Extras Detail');
        $proyek = CastingProject::factory()->create(['nama_produksi' => 'Film Riwayat']);
        ProjectApplication::create(['casting_project_id' => $proyek->id, 'extras_id' => $profil->id, 'status_partisipasi' => 'deal']);

        $this->actingAs($this->sa)->get(route('super-admin.admins.show', $profil->user_id))->assertOk()
            ->assertSee('Profil Extras')->assertSee('Film Riwayat')->assertSee('Aktivitas Akun Ini')
            ->assertSee(route('super-admin.admins.reset-password', $profil->user_id));
    }

    public static function bukanSa(): array
    {
        return [['admin'], ['korlap'], ['client'], ['extras']];
    }

    #[DataProvider('bukanSa')]
    public function test_non_sa_ditolak(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))->get(route('super-admin.akun.index'))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CI.2: notifikasi, nominal invoice, ACC, ganti email, dialog hapus permanen, Kelola Akun kartu. */
class CiDuaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'aktif'], $attrs));
    }

    private function proyek(array $attrs = []): CastingProject
    {
        return CastingProject::create(array_merge([
            'admin_id' => $this->user('admin')->id,
            'nama_produksi' => 'Proyek '.fake()->unique()->lexify('????'),
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ], $attrs));
    }

    public function test_halaman_notifikasi_paginasi_milik_sendiri_dan_tandai_semua(): void
    {
        $u = $this->user('extras');
        foreach (range(1, 22) as $i) {
            $u->kabari("Judul $i", 'Pesan', '/extras/dashboard');
        }
        $this->user('extras')->kabari('Punya Lain', 'x', '/');

        $this->actingAs($u)->get(route('notifications.index'))
            ->assertOk()->assertSee('Tandai semua dibaca')->assertDontSee('Punya Lain')->assertSee('page=2', false);
        $this->get(route('notifications.index', ['page' => 2]))->assertOk();

        $this->post(route('notifications.read-all'));
        $this->assertSame(0, $u->unreadNotifications()->count());
        $this->actingAs($u->fresh())->get(route('notifications.index'))->assertDontSee('Tandai semua dibaca');
    }

    public function test_lonceng_menaut_ke_halaman_notifikasi(): void
    {
        $u = $this->user('client');
        foreach (range(1, 7) as $i) {
            $u->kabari("N$i", 'Pesan', '/');
        }

        $this->actingAs($u)->get(route('client.profil'))->assertSee('href="'.route('notifications.index').'"', false)->assertSee('2 notifikasi lainnya');
    }

    public function test_notifikasi_butuh_login(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_nominal_invoice_satu_sumber_dan_tanpa_angka_kuota_kali_budget(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(['client_id' => $client->id, 'client_request_status' => 'disetujui']);
        $p->classes()->create(['nama_kelas' => 'Ibu-ibu', 'budget_client' => 111000, 'kuota_kelas' => 3]);
        $p->invoices()->create(['nominal' => 5000000]);

        $this->actingAs($client)->get(route('invoices.show', $p))->assertOk()
            ->assertSee('Rp 5.000.000')->assertDontSee('333.000')->assertDontSee('111.000')->assertDontSee('Fee per Orang');
        $this->get(route('invoices.index-client'))->assertOk()->assertSee('Rp 5.000.000');
    }

    public function test_invoice_tanpa_nominal_memakai_usulan_sebagai_isian_awal(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(['client_id' => $client->id, 'client_request_status' => 'disetujui']);
        $p->classes()->create(['nama_kelas' => 'Ibu-ibu', 'budget_client' => 100000, 'kuota_kelas' => 3]);
        $p->invoices()->create();

        $this->actingAs($client)->get(route('invoices.show', $p))->assertSee('Rp 300.000')->assertDontSee('Fee per Orang');
    }

    public function test_acc_ditolak_bila_kelas_atau_tanggal_kosong(): void
    {
        $sa = $this->user('super_admin');
        $p = $this->proyek(['client_id' => $this->user('client')->id, 'client_request_status' => 'menunggu_acc']);

        $this->actingAs($sa)->patch(route('super-admin.projects.acc', $p))->assertSessionHas('error', 'Lengkapi peran dan tanggal dulu.');
        $this->assertSame('menunggu_acc', $p->fresh()->client_request_status);

        $p->classes()->create(['nama_kelas' => 'Ibu-ibu', 'budget_client' => 1, 'kuota_kelas' => 1]);
        $this->patch(route('super-admin.projects.acc', $p))->assertSessionHas('error');
        $this->assertSame('menunggu_acc', $p->fresh()->client_request_status);

        $p->shootingDates()->create(['tanggal' => today()->addDays(5)]);
        $this->patch(route('super-admin.projects.acc', $p))->assertSessionHas('status');
        $this->assertSame('disetujui', $p->fresh()->client_request_status);
    }

    public function test_ganti_email_client_wajib_kata_sandi_saat_ini(): void
    {
        $c = $this->user('client', ['email' => 'lama@example.com']);
        $data = ['name' => $c->name, 'email' => 'baru@example.com'];

        $this->actingAs($c)->put(route('client.profil.update'), $data)->assertSessionHasErrors('current_password');
        $this->put(route('client.profil.update'), $data + ['current_password' => 'salah'])->assertSessionHasErrors('current_password');
        $this->assertSame('lama@example.com', $c->fresh()->email);

        $this->put(route('client.profil.update'), $data + ['current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('baru@example.com', $c->fresh()->email);
    }

    public function test_ubah_profil_tanpa_ganti_email_tidak_butuh_kata_sandi(): void
    {
        $c = $this->user('client', ['email' => 'sama@example.com']);

        $this->actingAs($c)->put(route('client.profil.update'), ['name' => 'Nama Baru', 'email' => 'sama@example.com'])->assertSessionHasNoErrors();
        $this->assertSame('Nama Baru', $c->fresh()->name);
    }

    public function test_dialog_hapus_permanen_memuat_jumlah_daftar_dan_kata_permanen(): void
    {
        $admin = $this->user('admin');
        $m = $this->user('extras', ['name' => 'Akun Mangkrak Satu', 'created_at' => now()->subDays(40)]);
        ExtrasProfile::create(['user_id' => $m->id]);

        $this->actingAs($admin)->get(route('admin.akun.extras'))->assertOk()
            ->assertSee('<dialog id="dialog-prune"', false)->assertSee('Akun Mangkrak Satu')->assertSee('permanen, tidak bisa dibatalkan', false)
            ->assertSee('Hapus 1 akun mangkrak');
    }

    public function test_kelola_akun_daftar_punya_markup_kartu_di_hp(): void
    {
        $admin = $this->user('admin');
        ExtrasProfile::create(['user_id' => $this->user('extras')->id]);

        $this->actingAs($admin)->get(route('admin.akun.extras', ['tampil' => 'daftar']))->assertOk()
            ->assertSee('class="akx-tabel"', false)->assertSee('data-label="Status"', false)->assertSee('max-width: 640px', false);
    }
}

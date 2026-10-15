<?php

namespace Tests\Feature;

use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isolasi simpan per-bagian, SPEC.md Bagian CL.2: menyimpan satu bagian
 * tidak boleh mengubah field milik bagian lain.
 */
class ClSimpanPerBagianTest extends TestCase
{
    use RefreshDatabase;

    private function profilLengkap(): ExtrasProfile
    {
        $user = User::factory()->create([
            'role' => 'extras',
            'username' => 'orisinal',
            'nomor_wa' => '628111111111',
        ]);

        $profile = ExtrasProfile::create([
            'user_id' => $user->id,
            'nama_asli' => 'Nama Orisinal',
            'usia' => 25,
            'gender' => 'wanita',
            'tinggi_badan' => 160,
            'berat_badan' => 50,
            'ukuran_baju' => 'M',
            'bahasa' => 'Indonesia, Inggris',
            'riwayat_pengalaman' => [['judul' => 'Figuran A', 'keterangan' => 'ket', 'tahun' => 2020]],
            'tautan_tambahan' => [['label' => 'IG', 'url' => 'https://instagram.com/orisinal']],
            'rate_card' => 500000,
            'izin_tampil_publik' => true,
        ]);

        $tagIds = ExtrasCategory::take(1)->pluck('id');
        if ($tagIds->isNotEmpty()) {
            $profile->categories()->sync($tagIds);
        }

        return $profile->fresh();
    }

    private function assertSemuaBagianLainUtuh(ExtrasProfile $before, User $userBefore, ExtrasProfile $after, User $userAfter, array $kecuali = []): void
    {
        $checks = [
            'nama_asli' => fn () => $this->assertSame($before->nama_asli, $after->nama_asli, 'nama_asli berubah'),
            'username' => fn () => $this->assertSame($userBefore->username, $userAfter->username, 'username berubah'),
            'usia' => fn () => $this->assertSame($before->usia, $after->usia, 'usia berubah'),
            'gender' => fn () => $this->assertSame($before->gender, $after->gender, 'gender berubah'),
            'tinggi_badan' => fn () => $this->assertSame($before->tinggi_badan, $after->tinggi_badan, 'tinggi_badan berubah'),
            'berat_badan' => fn () => $this->assertSame($before->berat_badan, $after->berat_badan, 'berat_badan berubah'),
            'ukuran_baju' => fn () => $this->assertSame($before->ukuran_baju, $after->ukuran_baju, 'ukuran_baju berubah'),
            'nomor_wa' => fn () => $this->assertSame($userBefore->nomor_wa, $userAfter->nomor_wa, 'nomor_wa berubah'),
            'bahasa' => fn () => $this->assertSame($before->bahasa, $after->bahasa, 'bahasa berubah'),
            'categories' => fn () => $this->assertSame($before->categories->pluck('id')->sort()->values()->all(), $after->categories->pluck('id')->sort()->values()->all(), 'tag berubah'),
            'riwayat_pengalaman' => fn () => $this->assertSame($before->riwayat_pengalaman, $after->riwayat_pengalaman, 'riwayat_pengalaman berubah'),
            'tautan_tambahan' => fn () => $this->assertSame($before->tautan_tambahan, $after->tautan_tambahan, 'tautan_tambahan berubah'),
            'rate_card' => fn () => $this->assertEquals($before->rate_card, $after->rate_card, 'rate_card berubah'),
            'izin_tampil_publik' => fn () => $this->assertSame($before->izin_tampil_publik, $after->izin_tampil_publik, 'izin_tampil_publik berubah'),
        ];

        foreach ($checks as $field => $assertFn) {
            if (in_array($field, $kecuali, true)) {
                continue;
            }
            $assertFn();
        }
    }

    public function test_simpan_kontak_saja_tidak_mengubah_bagian_lain(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'kontak',
            'nomor_wa' => '628199999999',
        ])->assertRedirect();

        $after = $profile->fresh();
        $userAfter = $user->fresh();

        $this->assertSame('628199999999', $userAfter->nomor_wa);
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter, ['nomor_wa']);
    }

    public function test_simpan_data_diri_saja_tidak_mengubah_bagian_lain(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'data_diri',
            'nama_asli' => 'Nama Diubah',
            'username' => 'username_baru',
            'usia' => 30,
            'gender' => 'pria',
            'tinggi_badan' => 170,
            'berat_badan' => 60,
            'ukuran_baju' => 'L',
        ])->assertRedirect();

        $after = $profile->fresh();
        $userAfter = $user->fresh();

        $this->assertSame('Nama Diubah', $after->nama_asli);
        $this->assertSame('username_baru', $userAfter->username);
        $this->assertSame(30, $after->usia);
        $this->assertSame('pria', $after->gender);
        $this->assertSame(170, $after->tinggi_badan);
        $this->assertSame(60, $after->berat_badan);
        $this->assertSame('L', $after->ukuran_baju);
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter, [
            'nama_asli', 'username', 'usia', 'gender', 'tinggi_badan', 'berat_badan', 'ukuran_baju',
        ]);
    }

    public function test_simpan_privasi_saja_tidak_mengubah_bagian_lain(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'privasi',
            'izin_present' => 1,
            // checkbox tidak dicentang -> tidak dikirim sama sekali
        ])->assertRedirect();

        $after = $profile->fresh();
        $userAfter = $user->fresh();

        $this->assertFalse($after->izin_tampil_publik);
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter, ['izin_tampil_publik']);
    }

    public function test_simpan_tautan_kosong_mengosongkan_tautan_tambahan_tapi_tidak_sentuh_bagian_lain(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;
        $this->assertNotEmpty($profile->tautan_tambahan);

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'tautan',
            'tautan_label' => [''],
            'tautan_url' => [''],
        ])->assertRedirect();

        $after = $profile->fresh();
        $userAfter = $user->fresh();

        $this->assertSame([], $after->tautan_tambahan);
        // rate_card ikut di-submit kosong oleh form Tautan&Tarif yang sama,
        // jadi field itu TIDAK termasuk "bagian lain" - nilainya sah berubah
        // mengikuti request bagian tautan itu sendiri.
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter, ['tautan_tambahan', 'rate_card']);
    }

    public function test_nama_asli_dan_username_tidak_wajib_saat_simpan_kontak(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'username' => null]);
        // Belum ada baris extras_profiles sama sekali (akun baru).
        $this->assertNull($user->extrasProfile);

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'kontak',
            'nomor_wa' => '628177777777',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('628177777777', $user->nomor_wa);
        $this->assertNull($user->username);
        $this->assertNotNull($user->extrasProfile);
        $this->assertNull($user->extrasProfile->nama_asli);
    }

    public function test_bagian_hilang_ditolak_validasi_tanpa_perubahan_db(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'nomor_wa' => '628100000000',
        ])->assertSessionHasErrors('bagian');

        $after = $profile->fresh();
        $userAfter = $user->fresh();
        $this->assertSame($user->nomor_wa, $userAfter->nomor_wa);
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter);
    }

    public function test_bagian_tidak_valid_ditolak_validasi_tanpa_perubahan_db(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'rekening', // bukan salah satu dari 6 nilai sah
            'nomor_wa' => '628100000000',
        ])->assertSessionHasErrors('bagian');

        $after = $profile->fresh();
        $userAfter = $user->fresh();
        $this->assertSame($user->nomor_wa, $userAfter->nomor_wa);
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter);
    }

    public function test_simpan_pengalaman_saja_tidak_mengubah_bagian_lain(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'pengalaman',
            'pengalaman_judul' => ['Peran Baru'],
            'pengalaman_keterangan' => ['ket baru'],
            'pengalaman_tahun' => [2024],
        ])->assertRedirect();

        $after = $profile->fresh();
        $userAfter = $user->fresh();

        $this->assertCount(1, $after->riwayat_pengalaman);
        $this->assertSame('Peran Baru', $after->riwayat_pengalaman[0]['judul']);
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter, ['riwayat_pengalaman']);
    }

    public function test_simpan_tentang_saja_tidak_mengubah_bagian_lain(): void
    {
        $profile = $this->profilLengkap();
        $user = $profile->user;

        $this->actingAs($user)->put('/extras/profil', [
            'bagian' => 'tentang',
            'bahasa' => ['Jawa'],
        ])->assertRedirect();

        $after = $profile->fresh();
        $userAfter = $user->fresh();

        $this->assertSame('Jawa', $after->bahasa);
        // categories_present tidak dikirim -> tag tidak boleh berubah
        $this->assertSemuaBagianLainUtuh($profile, $user, $after, $userAfter, ['bahasa']);
    }
}

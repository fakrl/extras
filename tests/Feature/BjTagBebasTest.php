<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC BJ.1: tag bebas + normalisasi + autocomplete + Kelola Tag.
 */
class BjTagBebasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ExtrasProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExtrasCategorySeeder::class);
        $this->user = User::factory()->create(['role' => 'extras', 'username' => 'rina']);
        $this->profile = ExtrasProfile::create(['user_id' => $this->user->id]);
    }

    private function simpan(array $tag)
    {
        return $this->actingAs($this->user)->put('/extras/profil', ['nama_asli' => 'Rina', 'username' => 'rina', 'categories_present' => 1, 'tag_nama' => $tag]);
    }

    public function test_normalisasi(): void
    {
        $this->assertSame('Bisa silat', ExtrasCategory::normalisasi('  #Bisa   silat '));
        $this->assertNull(ExtrasCategory::normalisasi(' # '));
        $this->assertSame(30, mb_strlen(ExtrasCategory::normalisasi(str_repeat('a', 50))));
    }

    public function test_pakai_tag_lama_tanpa_beda_huruf_dan_buat_tag_baru(): void
    {
        $jumlah = ExtrasCategory::count();

        $this->simpan(['dewasa muda', '#Bisa  silat ', 'bisa silat'])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($jumlah + 1, ExtrasCategory::count());
        $baru = ExtrasCategory::where('nama', 'Bisa silat')->firstOrFail();
        $this->assertNull($baru->grup);
        $this->assertSame($this->user->id, $baru->dibuat_oleh);
        $this->assertEqualsCanonicalizing(['Dewasa muda', 'Bisa silat'], $this->profile->categories()->pluck('nama')->all());
        $this->assertTrue(ActivityLog::where('action', 'TAG_DIBUAT')->exists());

        $this->actingAs($this->user)->get(route('extras.profile.edit'))->assertOk()
            ->assertSee('data-tag-input', false)
            ->assertSee('value="Bisa silat"', false)
            ->assertSee('data-tag-saran="Naik motor"', false);
    }

    public function test_maks_15_tag(): void
    {
        $tags = array_map(fn ($i) => "Tag {$i}", range(1, 16));
        $this->simpan($tags)->assertSessionHasErrors('tag_nama');
        $this->assertSame(0, $this->profile->categories()->count());
        $this->assertFalse(ExtrasCategory::where('nama', 'Tag 1')->exists());

        $this->simpan([...array_slice($tags, 0, 15), 'tag 1'])->assertSessionHasNoErrors();
        $this->assertSame(15, $this->profile->categories()->count());
    }

    public function test_admin_ubah_tag_extras_tercatat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->profile->categories()->attach(ExtrasCategory::where('nama', 'Remaja')->value('id'));

        $this->actingAs($admin)->patch(route('admin.users.kategori', $this->user), ['tag_nama' => ['Berhijab', 'Pemain biola']])->assertRedirect();

        $this->assertEqualsCanonicalizing(['Berhijab', 'Pemain biola'], $this->profile->categories()->pluck('nama')->all());
        $log = ActivityLog::where('action', 'UPDATE_EXTRAS_KATEGORI')->latest('id')->firstOrFail();
        $this->assertEqualsCanonicalizing(['Berhijab', 'Pemain biola'], $log->properties['ditambah']);
        $this->assertSame(['Remaja'], $log->properties['dihapus']);
    }

    public function test_tag_per_peran_pakai_nama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.projects.store'), [
            'nama_produksi' => 'Proyek Tag', 'client_id' => User::factory()->create(['role' => 'client'])->id,
            'deadline' => now()->addDays(7)->toDateString(), 'kuota' => 5, 'tanggal_shooting' => [now()->addDays(10)->toDateString()],
            'kelas' => [['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2, 'tag_nama' => ['naik motor', 'Main gitar']]],
        ])->assertRedirect(route('admin.projects.index'));

        $kelas = CastingProject::where('nama_produksi', 'Proyek Tag')->firstOrFail()->classes()->firstOrFail();
        $this->assertEqualsCanonicalizing(['Naik motor', 'Main gitar'], $kelas->categories()->pluck('nama')->all());
    }

    public function test_autocomplete(): void
    {
        $this->getJson(route('tag.cari'))->assertUnauthorized();
        $motor = ExtrasCategory::where('nama', 'Naik motor')->firstOrFail();
        $motor->extrasProfiles()->attach($this->profile->id);
        ExtrasCategory::create(['nama' => 'Motor cross']);

        $this->actingAs($this->user)->getJson(route('tag.cari', ['q' => 'MOTOR']))->assertOk()->assertExactJson(['Naik motor', 'Motor cross']);
        $this->assertCount(10, $this->actingAs($this->user)->getJson(route('tag.cari'))->json());
    }

    public function test_kelola_tag_ubah_grup_dan_gabung(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $asal = ExtrasCategory::create(['nama' => 'Bisa silat']);
        $tujuan = ExtrasCategory::create(['nama' => 'Silat', 'grup' => 'Kemampuan']);
        $lain = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
        $this->profile->categories()->attach([$asal->id, $tujuan->id]);
        $lain->categories()->attach($asal->id);
        $kelas = CastingProject::factory()->create()->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $kelas->categories()->attach($asal->id);

        $this->actingAs($admin)->get(route('admin.tags.index'))->assertOk()->assertSee('#Bisa silat')->assertSee('Gabung');
        $this->actingAs(User::factory()->create(['role' => 'extras']))->get(route('admin.tags.index'))->assertForbidden();

        $this->actingAs($admin)->patch(route('admin.tags.update', $asal), ['grup' => 'Tipe'])->assertRedirect();
        $this->assertSame('Tipe', $asal->fresh()->grup);
        $this->actingAs($admin)->patch(route('admin.tags.update', $asal), ['grup' => 'Ngawur'])->assertSessionHasErrors('grup');

        $this->actingAs($admin)->post(route('admin.tags.gabung', $asal), ['tujuan' => 'silat'])->assertRedirect()->assertSessionHas('status');

        $this->assertNull(ExtrasCategory::find($asal->id));
        $this->assertSame([$tujuan->id], $this->profile->categories()->pluck('extras_categories.id')->all());
        $this->assertSame([$tujuan->id], $lain->categories()->pluck('extras_categories.id')->all());
        $this->assertSame([$tujuan->id], $kelas->categories()->pluck('extras_categories.id')->all());
        $this->assertSame(['TAG_UBAH_GRUP', 'TAG_GABUNG'], ActivityLog::whereIn('action', ['TAG_UBAH_GRUP', 'TAG_GABUNG'])->orderBy('id')->pluck('action')->unique()->values()->all());

        $this->actingAs($admin)->post(route('admin.tags.gabung', $tujuan), ['tujuan' => 'Silat'])->assertSessionHas('error');
    }

    public function test_publik_tanpa_tag_lainnya_dan_look(): void
    {
        $this->profile->generateShareToken();
        $lainnya = ExtrasCategory::create(['nama' => 'Bisa silat']);
        $this->profile->categories()->attach([$lainnya->id, ExtrasCategory::where('nama', 'Jawa')->value('id'), ExtrasCategory::where('nama', 'Menari')->value('id')]);
        $url = route('public.extras.profile', $this->profile->fresh()->share_token);

        $this->get($url)->assertOk()->assertSee('#Menari')->assertDontSee('#Bisa silat')->assertDontSee('#Jawa');
        $this->actingAs($this->user)->get(route('extras.profile.show'))->assertSee('#Bisa silat');

        $lainnya->update(['grup' => 'Kemampuan']);
        $this->get($url)->assertSee('#Bisa silat');
    }
}

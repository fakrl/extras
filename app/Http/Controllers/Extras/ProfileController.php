<?php

namespace App\Http\Controllers\Extras;

use App\Exceptions\NikDuplikatException;
use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Rules\NomorWa;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Tampilan read-only, persis apa yang dilihat Admin saat cross-check
     * (RF-14), supaya Extras bisa tau "gini nih tampilan gua" sebelum/sesudah
     * isi profil. Beda dari edit() yang isinya form input.
     */
    public function show(Request $request)
    {
        $profile = $request->user()->extrasProfile;

        // Akun yang belum pernah isi profil sama sekali: view read-only ini
        // asumsi $profile selalu ada (foto_profil_path, grade_saat_ini, dst
        // diakses langsung tanpa null-check). Daripada bikin view null-safe
        // di puluhan tempat, arahkan ke form lengkapi profil dulu.
        if (! $profile) {
            return redirect()->route('extras.profile.edit')
                ->with('error', 'Lengkapi profil kamu dulu sebelum bisa melihat tampilan profil.');
        }

        $profile->generateShareToken();

        return view('extras.profile-show', ['profile' => $profile]);
    }

    /**
     * RF-06: Extras melengkapi profil (usia, gender, tinggi badan, ukuran
     * baju, warna kulit, riwayat pengalaman, bahasa, rate card, video, foto,
     * portofolio/sosmed).
     */
    public function edit(Request $request)
    {
        // Sama kayak show(): akun yang extras_profiles-nya kosong (seharusnya
        // tidak terjadi lewat alur registrasi normal - lihat RegisterController
        // - tapi bisa kejadian di akun test/seed manual) butuh instance kosong
        // biar form render, bukan crash. update() di bawah bikin baris
        // extras_profiles begitu bagian pertama disimpan (lihat profilAtauBaru()).
        $profile = $request->user()->extrasProfile;
        if (! $profile) {
            $profile = new ExtrasProfile;
            // View baca $profile->user->username/nomor_wa (kolom lintas-role
            // yang disimpan di tabel users, bukan extras_profiles) - instance
            // baru belum tersimpan jadi relasi ini nggak bisa di-lazy-load
            // dari DB, set manual dari user yang lagi login.
            $profile->setRelation('user', $request->user());
        }

        // CL.1.2: status kelengkapan pakai kriteria yang sama persis dengan
        // profilLengkap(), cuma dipecah per field biar bisa ditautkan ke
        // bagiannya masing-masing.
        $kurangDaftar = collect([
            'foto_profil_path' => ['label' => 'foto', 'anchor' => 'bagian-foto'],
            'usia' => ['label' => 'usia', 'anchor' => 'bagian-data_diri'],
            'gender' => ['label' => 'jenis kelamin', 'anchor' => 'bagian-data_diri'],
            'tinggi_badan' => ['label' => 'tinggi badan', 'anchor' => 'bagian-data_diri'],
        ])->filter(fn ($info, $field) => ! $profile->{$field})->values();

        // CL.1.5: akun baru/profil belum lengkap, bagian Data Diri auto-terbuka.
        $dataDiriBelumLengkap = ! $profile->nama_asli || ! $profile->user->username
            || ! $profile->usia || ! $profile->gender || ! $profile->tinggi_badan;

        return view('extras.profile-edit', [
            'profile' => $profile,
            'fotoTambahan' => $this->fotoTambahanPerSlot($profile),
            'tagGroups' => ExtrasCategory::perGrup(),
            'kurangDaftar' => $kurangDaftar,
            'dataDiriBelumLengkap' => $dataDiriBelumLengkap,
        ]);
    }

    /**
     * Array 4 slot (index 1-4), isi path foto kalau ada atau null kalau
     * kosong, biar view tinggal loop 1..4 tanpa perlu cek collection manual.
     */
    private function fotoTambahanPerSlot(?ExtrasProfile $profile): array
    {
        if (! $profile) {
            return [1 => null, 2 => null, 3 => null, 4 => null];
        }

        $bySlot = $profile->fotoTambahan();

        return [
            1 => $bySlot->get(1),
            2 => $bySlot->get(2),
            3 => $bySlot->get(3),
            4 => $bySlot->get(4),
        ];
    }

    /**
     * CL.2: profil sekarang mode baca + ubah per bagian, satu submit cuma
     * menyimpan satu bagian (field `bagian` tersembunyi dari form). Validasi
     * & penulisan kolom di-scope ketat ke bagian itu saja lewat method
     * simpan*() di bawah, supaya bagian lain yang sedang tidak diubah tidak
     * ikut tertimpa/terhapus.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'bagian' => ['required', Rule::in(['data_diri', 'kontak', 'tentang', 'pengalaman', 'tautan', 'privasi'])],
        ]);
        $bagian = $request->string('bagian')->value();

        $profile = match ($bagian) {
            'data_diri' => $this->simpanDataDiri($request),
            'kontak' => $this->simpanKontak($request),
            'tentang' => $this->simpanTentang($request),
            'pengalaman' => $this->simpanPengalaman($request),
            'tautan' => $this->simpanTautan($request),
            'privasi' => $this->simpanPrivasi($request),
        };

        // Efek samping lama, tetap jalan setelah simpan bagian APA PUN (bukan
        // cuma Data Diri/Privasi), persis seperti sebelum CL dipecah per bagian.
        if (! $profile->izin_tampil_publik && $profile->tampil_di_beranda) {
            $profile->forceFill(['tampil_di_beranda' => false, 'tampil_di_beranda_at' => null])->save();
        }
        $profile->siapkanKontrakLolos();

        // SPEC.md Bagian B5: return-to-intent begitu lengkapi-profil (langkah
        // WAJIB pasca-registrasi, RF-06, TIDAK di-skip) selesai, kalau
        // datang dari link event publik dan proyeknya masih valid, lempar ke
        // halaman apply proyek itu, bukan ke halaman profil biasa. Dipanggil
        // di update() (bukan cuma dari alur registrasi) SENGAJA, self-
        // correcting lewat session pull(), cuma "nyala" sekali tepat setelah
        // token itu di-set, lalu hilang, jadi aman dipanggil di sini juga
        // untuk edit profil biasa berikutnya (session key sudah kosong).
        $eventToken = $request->session()->pull('intended_event_token');
        if ($eventToken) {
            $project = CastingProject::where('share_token', $eventToken)->first();

            if ($project && $project->menerimaPendaftaran()) {
                return redirect()->route('extras.projects.show', $project)
                    ->with('status', 'Profil berhasil disimpan. Yuk lanjut apply ke proyek ini!');
            }

            return redirect(route('extras.profile.edit').'#bagian-'.$bagian)->with('status', 'Profil berhasil disimpan. Sayangnya proyek dari link yang kamu buka sudah tidak menerima pendaftaran.');
        }

        // CL.1.1: balik ke halaman Profil, di anchor bagian yang baru disimpan.
        return redirect(route('extras.profile.edit').'#bagian-'.$bagian)->with('status', 'Profil berhasil disimpan.');
    }

    /** Baris extras_profiles dibuat begitu bagian pertama disimpan (dulu lewat updateOrCreate() di update()). */
    private function profilAtauBaru(Request $request): ExtrasProfile
    {
        return $request->user()->extrasProfile ?: $request->user()->extrasProfile()->create([]);
    }

    private function simpanDataDiri(Request $request): ExtrasProfile
    {
        $data = $request->validate([
            'nama_asli' => ['required', 'string', 'max:255'],
            // Identifier login alternatif: alpha_dash saja (tanpa spasi).
            'username' => [
                'required', 'string', 'alpha_dash', 'max:50',
                Rule::unique('users', 'username')->ignore($request->user()->id),
            ],
            'usia' => ['nullable', 'integer', 'min:1', 'max:120'],
            'gender' => ['nullable', 'in:pria,wanita'],
            'tinggi_badan' => ['nullable', 'integer', 'min:1'],
            'berat_badan' => ['nullable', 'integer', 'min:20', 'max:250'],
            'ukuran_baju' => ['nullable', 'string'],
        ]);

        $profile = $this->profilAtauBaru($request);
        $profile->update(collect($data)->except('username')->toArray());

        // username ada di tabel users (reusable lintas role), BUKAN extras_profiles.
        $request->user()->update(['username' => $data['username']]);

        return $profile;
    }

    private function simpanKontak(Request $request): ExtrasProfile
    {
        $data = $request->validate([
            'nomor_wa' => ['nullable', 'string', 'max:20', new NomorWa],
        ]);

        $request->user()->update(['nomor_wa' => $data['nomor_wa'] ?? null]);

        return $this->profilAtauBaru($request);
    }

    private function simpanTentang(Request $request): ExtrasProfile
    {
        $data = $request->validate([
            'bahasa' => is_array($request->input('bahasa')) ? ['nullable', 'array', 'max:10'] : ['nullable', 'string'],
            'bahasa.*' => ['nullable', 'string', 'max:40'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:extras_categories,id'],
            'tag_nama' => ['nullable', 'array'],
            'tag_nama.*' => ['nullable', 'string', 'max:100'],
        ], [
            'bahasa.max' => 'Maksimal 10 bahasa.',
        ]);

        $tagIds = $request->boolean('categories_present')
            ? ExtrasCategory::idsDariInput($data['tag_nama'] ?? [], $data['categories'] ?? [], $request->user())
            : null;

        if (is_array($data['bahasa'] ?? null)) {
            $data['bahasa'] = collect($data['bahasa'])->map(fn ($b) => trim((string) $b))->filter()
                ->unique(fn ($b) => mb_strtolower($b))->implode(', ') ?: null;
        }

        $profile = $this->profilAtauBaru($request);
        $profile->update(['bahasa' => $data['bahasa'] ?? null]);
        if ($tagIds !== null) {
            $profile->categories()->sync($tagIds);
        }

        return $profile;
    }

    private function simpanPengalaman(Request $request): ExtrasProfile
    {
        $data = $request->validate([
            'pengalaman_judul' => ['nullable', 'array', 'max:20'],
            'pengalaman_judul.*' => ['nullable', 'required_with:pengalaman_keterangan.*,pengalaman_tahun.*', 'string', 'max:150'],
            'pengalaman_keterangan' => ['nullable', 'array', 'max:20'],
            'pengalaman_keterangan.*' => ['nullable', 'string', 'max:255'],
            'pengalaman_tahun' => ['nullable', 'array', 'max:20'],
            'pengalaman_tahun.*' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
        ], [
            'pengalaman_judul.*.required_with' => 'Judul pengalaman wajib diisi.',
            'pengalaman_judul.max' => 'Maksimal 20 pengalaman.',
        ]);

        $profile = $this->profilAtauBaru($request);

        // Perilaku lama: riwayat_pengalaman cuma ditulis kalau pengalaman_judul
        // dikirim di request (array kosong pun sah, berarti sengaja dikosongkan).
        if ($request->has('pengalaman_judul')) {
            $profile->update(['riwayat_pengalaman' => collect($data['pengalaman_judul'] ?? [])->filter()->map(fn ($judul, $i) => [
                'judul' => $judul,
                'keterangan' => $data['pengalaman_keterangan'][$i] ?? null,
                'tahun' => isset($data['pengalaman_tahun'][$i]) ? (int) $data['pengalaman_tahun'][$i] : null,
            ])->values()->all()]);
        }

        return $profile;
    }

    private function simpanTautan(Request $request): ExtrasProfile
    {
        $data = $request->validate([
            // Tautan tambahan (sosmed/portofolio, jumlah bebas via tombol "+"):
            // CLAUDE.md §5, hanya dilihat Extras & Admin, TIDAK PERNAH
            // dikirim ke view Client.
            'tautan_label' => ['nullable', 'array'],
            'tautan_label.*' => ['nullable', 'string', 'max:100', 'required_with:tautan_url.*'],
            'tautan_url' => ['nullable', 'array'],
            'tautan_url.*' => ['nullable', 'url', 'max:500', 'required_with:tautan_label.*'],
            'rate_card' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tautanTambahan = [];
        foreach ($data['tautan_label'] ?? [] as $i => $label) {
            $url = $data['tautan_url'][$i] ?? null;
            if ($label && $url) {
                $tautanTambahan[] = ['label' => $label, 'url' => $url];
            }
        }

        $profile = $this->profilAtauBaru($request);
        $profile->update(['tautan_tambahan' => $tautanTambahan, 'rate_card' => $data['rate_card'] ?? null]);

        return $profile;
    }

    private function simpanPrivasi(Request $request): ExtrasProfile
    {
        $profile = $this->profilAtauBaru($request);
        if ($request->boolean('izin_present')) {
            $profile->update(['izin_tampil_publik' => $request->boolean('izin_tampil_publik')]);
        }

        return $profile;
    }

    public function generateShareLink(Request $request): JsonResponse
    {
        $profile = $request->user()->extrasProfile;
        $token = $profile->generateShareToken();

        return response()->json([
            'token' => $token,
            'url' => route('public.extras.profile', $token),
        ]);
    }

    /**
     * RF-04: form KTP+rekening, cuma muncul setelah Extras dinyatakan lolos
     * (halaman kontrak menautkan ke sini kalau NIK belum ada).
     * SENGAJA terpisah dari profile-edit biasa (data minimization UU PDP).
     */
    public function lengkapiKtp(Request $request, ProjectApplication $application)
    {
        $this->pastikanMilikSendiri($request, $application);

        return view('extras.lengkapi-ktp', compact('application'));
    }

    public function simpanKtp(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);

        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'rekening' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $application->extras->lengkapiKtp($data['nik'], $data['rekening'] ?? null);
        } catch (NikDuplikatException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->with('error', 'NIK ini sudah terdaftar di akun lain, hubungi Admin kalau ini kesalahan.');
        }

        $application->extras->siapkanKontrakLolos();

        return redirect()->route('contracts.show', $application)->with('status', 'NIK & rekening berhasil disimpan.');
    }

    private function pastikanMilikSendiri(Request $request, ProjectApplication $application): void
    {
        abort_unless($application->extras_id === $request->user()->extrasProfile->id, 403);
        abort_unless($application->status_partisipasi === 'lolos', 403);
    }

    /**
     * RF-06: upload/ganti foto profil. Terpisah dari update() biasa karena
     * file harus divalidasi jenis & ukurannya, dan path hasil upload TIDAK
     * boleh datang dari request langsung (lihat catatan mass-assignment di
     * model ExtrasProfile).
     */
    public function uploadFoto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], // maks 5MB
        ]);

        $request->user()->extrasProfile->simpanFoto($request->file('foto'));

        return redirect('/extras/profil/lengkapi')->with('status', 'Foto profil berhasil diperbarui.');
    }

    /**
     * RF-06: upload/ganti video perkenalan.
     */
    public function uploadVideo(Request $request): RedirectResponse
    {
        $request->validate([
            'video' => ['required', 'mimes:mp4,mov,webm', 'max:51200'], // maks 50MB
        ]);

        $request->user()->extrasProfile->simpanVideo($request->file('video'));

        return redirect('/extras/profil/lengkapi')->with('status', 'Video perkenalan berhasil diperbarui.');
    }

    /**
     * RF-06 (perluasan): upload/ganti foto tambahan di slot 1-4 (foto model/
     * visual sisi lain, di luar foto profil utama). Slot yang sama di-replace,
     * bukan menumpuk baris baru.
     */
    public function uploadFotoTambahan(Request $request, int $slot): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], // maks 5MB
        ]);

        $request->user()->extrasProfile->simpanFotoTambahan($slot, $request->file('foto'));

        return redirect('/extras/profil/lengkapi')->with('status', 'Foto berhasil diperbarui.');
    }

    public function uploadFotoJson(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $profile = $request->user()->extrasProfile;
        $profile->simpanFoto($request->file('foto'));

        return response()->json([
            'url' => route('extras.media.foto', $profile).'?t='.time(),
        ]);
    }

    public function uploadVideoJson(Request $request): JsonResponse
    {
        $request->validate([
            'video' => ['required', 'mimes:mp4,mov,webm', 'max:51200'],
        ]);

        $profile = $request->user()->extrasProfile;
        $profile->simpanVideo($request->file('video'));

        return response()->json([
            'url' => route('extras.media.video', $profile).'?t='.time(),
        ]);
    }

    public function uploadFotoTambahanJson(Request $request, int $slot): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $profile = $request->user()->extrasProfile;
        $profile->simpanFotoTambahan($slot, $request->file('foto'));

        return response()->json([
            'url' => route('extras.media.foto-tambahan', [$profile, $slot]).'?t='.time(),
        ]);
    }

    /**
     * Hapus foto tambahan di slot tertentu, slot jadi kosong lagi.
     */
    public function hapusFotoTambahan(Request $request, int $slot): RedirectResponse
    {
        $request->user()->extrasProfile->hapusFotoTambahan($slot);

        return redirect('/extras/profil/lengkapi')->with('status', 'Foto berhasil dihapus.');
    }

    /**
     * Serve foto tambahan (slot 1-4) dari private disk. Otorisasi sama
     * seperti foto profil utama (pemilik/Admin/Client).
     */
    public function fotoTambahanStream(Request $request, ExtrasProfile $extrasProfile, int $slot): StreamedResponse
    {
        $this->pastikanBolehLihatMedia($request, $extrasProfile);

        $path = $extrasProfile->fotoTambahan()->get($slot);
        abort_unless($path, 404);

        return Storage::disk('local')->response($path);
    }

    /**
     * Serve foto profil dari private disk. Otorisasi manual (bukan cuma role
     * middleware) karena resource yang sama diakses beberapa pihak berbeda:
     * pemilik sendiri, Admin (semua), atau Client (RF-14 & CLAUDE.md
     * §5, foto/video boleh dilihat Client, beda dari sosmed/portofolio yang tidak).
     */
    public function fotoStream(Request $request, ExtrasProfile $extrasProfile): StreamedResponse
    {
        $this->pastikanBolehLihatMedia($request, $extrasProfile);

        abort_unless($extrasProfile->foto_profil_path, 404);

        return Storage::disk('local')->response($extrasProfile->foto_profil_path);
    }

    /**
     * Serve video perkenalan dari private disk. Otorisasi sama seperti foto.
     */
    public function videoStream(Request $request, ExtrasProfile $extrasProfile): StreamedResponse
    {
        $this->pastikanBolehLihatMedia($request, $extrasProfile);

        abort_unless($extrasProfile->video_profil_path, 404);

        return Storage::disk('local')->response($extrasProfile->video_profil_path);
    }

    private function pastikanBolehLihatMedia(Request $request, ExtrasProfile $extrasProfile): void
    {
        $user = $request->user();

        $bolehLihat = $user->id === $extrasProfile->user_id
            || $user->isAnyAdmin()
            || ($user->isClient() && $extrasProfile->applications()->terlihatClient()
                ->whereHas('castingProject', fn ($q) => $q->milikClient($user))->exists());

        abort_unless($bolehLihat, 403, 'Anda tidak memiliki akses ke media ini.');
    }
}

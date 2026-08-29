<?php

namespace App\Http\Controllers\Extras;

use App\Exceptions\NikDuplikatException;
use App\Http\Controllers\Controller;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Tampilan read-only — persis apa yang dilihat Admin saat cross-check
     * (RF-14), supaya Extras bisa tau "gini nih tampilan gua" sebelum/sesudah
     * isi profil. Beda dari edit() yang isinya form input.
     */
    public function show(Request $request)
    {
        $profile = $request->user()->extrasProfile;

        return view('extras.profile-show', [
            'profile' => $profile,
            'fotoTambahan' => $this->fotoTambahanPerSlot($profile),
        ]);
    }

    /**
     * RF-06: Extras melengkapi profil (usia, gender, tinggi badan, ukuran
     * baju, warna kulit, pengalaman, bahasa, rate card, video, foto,
     * portofolio/sosmed).
     */
    public function edit(Request $request)
    {
        $profile = $request->user()->extrasProfile;

        return view('extras.profile-edit', [
            'profile' => $profile,
            'fotoTambahan' => $this->fotoTambahanPerSlot($profile),
        ]);
    }

    /**
     * Array 4 slot (index 1-4), isi ExtrasPhoto kalau ada atau null kalau
     * kosong — biar view tinggal loop 1..4 tanpa perlu cek collection manual.
     */
    private function fotoTambahanPerSlot(ExtrasProfile $profile): array
    {
        $bySlot = $profile->photos->keyBy('urutan');

        return [
            1 => $bySlot->get(1),
            2 => $bySlot->get(2),
            3 => $bySlot->get(3),
            4 => $bySlot->get(4),
        ];
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alias' => ['required', 'string', 'max:255'],
            'usia' => ['nullable', 'integer', 'min:1', 'max:120'],
            'gender' => ['nullable', 'string'],
            'tinggi_badan' => ['nullable', 'integer'],
            'ukuran_baju' => ['nullable', 'string'],
            'warna_kulit' => ['nullable', 'string'],
            'pengalaman' => ['nullable', 'string'],
            'bahasa' => ['nullable', 'string'],
            // Tautan tambahan (sosmed/portofolio, jumlah bebas via tombol "+"):
            // CLAUDE.md §5 — hanya dilihat Extras & Admin, TIDAK PERNAH
            // dikirim ke view Casting Director.
            'tautan_label' => ['nullable', 'array'],
            'tautan_label.*' => ['nullable', 'string', 'max:100'],
            'tautan_url' => ['nullable', 'array'],
            'tautan_url.*' => ['nullable', 'url', 'max:500'],
            'rate_card' => ['nullable', 'numeric', 'min:0'],
            'nomor_wa' => ['nullable', 'string'],
        ]);

        $tautanTambahan = [];
        foreach ($data['tautan_label'] ?? [] as $i => $label) {
            $url = $data['tautan_url'][$i] ?? null;
            if ($label && $url) {
                $tautanTambahan[] = ['label' => $label, 'url' => $url];
            }
        }

        $dataDisimpan = collect($data)->except(['tautan_label', 'tautan_url', 'nomor_wa'])->toArray();
        $dataDisimpan['tautan_tambahan'] = $tautanTambahan;

        // SENGAJA tidak menerima 'status', 'cancel_count', 'foto_profil_path',
        // atau 'video_profil_path' dari request ini — kolom-kolom itu tidak
        // ada di $fillable ExtrasProfile, jadi mass-update() di bawah otomatis
        // aman (lihat catatan di model).
        $request->user()->extrasProfile->update($dataDisimpan);

        // nomor_wa ada di tabel users (Session 8, reusable lintas role),
        // BUKAN extras_profiles — simpan terpisah dari update() di atas.
        $request->user()->update(['nomor_wa' => $data['nomor_wa'] ?? null]);

        return redirect('/extras/profil')->with('status', 'Profil berhasil disimpan. Begini tampilannya buat Admin & Casting Director:');
    }

    /**
     * RF-04: form KTP+rekening, cuma muncul setelah Extras dinyatakan lolos
     * (ContractController::show() redirect ke sini kalau data belum lengkap).
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

    /**
     * Hapus foto tambahan di slot tertentu — slot jadi kosong lagi.
     */
    public function hapusFotoTambahan(Request $request, int $slot): RedirectResponse
    {
        $request->user()->extrasProfile->hapusFotoTambahan($slot);

        return redirect('/extras/profil/lengkapi')->with('status', 'Foto berhasil dihapus.');
    }

    /**
     * Serve foto tambahan (slot 1-4) dari private disk. Otorisasi sama
     * seperti foto profil utama (pemilik/Admin/CD).
     */
    public function fotoTambahanStream(Request $request, ExtrasProfile $extrasProfile, int $slot): StreamedResponse
    {
        $this->pastikanBolehLihatMedia($request, $extrasProfile);

        $foto = $extrasProfile->photos()->where('urutan', $slot)->first();
        abort_unless($foto, 404);

        return Storage::disk('local')->response($foto->path);
    }

    /**
     * Serve foto profil dari private disk. Otorisasi manual (bukan cuma role
     * middleware) karena resource yang sama diakses beberapa pihak berbeda:
     * pemilik sendiri, Admin (semua), atau Casting Director (RF-14 & CLAUDE.md
     * §5 — foto/video boleh dilihat CD, beda dari sosmed/portofolio yang tidak).
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
            || $user->isCastingDirector();

        abort_unless($bolehLihat, 403, 'Anda tidak memiliki akses ke media ini.');
    }
}

<?php

namespace App\Models;

use App\Exceptions\NikDuplikatException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// 'status' & 'cancel_count' SENGAJA tidak masuk $fillable — itu hasil
// kalkulasi sistem (RF-07/RF-08), cuma boleh berubah lewat recordCancellation()
// di bawah, bukan lewat mass-update dari request Extras sendiri.
// Lihat SECURITY-CHECKLIST.md poin 8 (Block field tampering).
//
// 'foto_profil_path' & 'video_profil_path' TETAP masuk $fillable, tapi
// proteksinya bukan dari $fillable — itu cuma whitelist teknis biar
// update() di dalam simpanFoto()/simpanVideo() nggak ditolak Laravel.
// Proteksi sebenarnya: tidak ada route/controller yang nerima input
// 'foto_profil_path' atau 'video_profil_path' langsung dari request user
// (lihat ProfileController::update() — field itu nggak divalidasi/dikirim
// di sana). Satu-satunya jalur yang mengisi kedua field ini adalah
// simpanFoto()/simpanVideo() di bawah, yang dipanggil dari endpoint upload
// khusus yang sudah validasi file (UploadedFile asli) + otorisasi pemilik.
#[Fillable([
    'user_id',
    'nik',
    'nama_asli',
    'alias',
    'usia',
    'gender',
    'tinggi_badan',
    'ukuran_baju',
    'warna_kulit',
    'pengalaman',
    'bahasa',
    'tautan_tambahan',
    'rate_card',
    'rekening',
    'foto_profil_path',
    'video_profil_path',
])]
class ExtrasProfile extends Model
{
    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'nama_asli' => 'encrypted',
            'rekening' => 'encrypted',
            // Array of {label, url} — RF-14 & CLAUDE.md §5: cuma dilihat
            // Extras & Admin, tidak pernah dikirim ke view Casting Director.
            'tautan_tambahan' => 'array',
        ];
    }

    /**
     * RF-04: satu-satunya titik yang mengisi nik_hash — kolom teknis untuk
     * lookup duplikat, karena `nik` di-cast `encrypted` (IV random, tidak
     * bisa di-WHERE). Set-only (tidak ada `get`) supaya cast `encrypted`
     * bawaan tetap jalan normal saat baca. Return array men-set kedua kolom
     * sekaligus (lihat HasAttributes::setAttributeMarkedMutatedAttributeValue).
     */
    protected function nik(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                if (! $value) {
                    return ['nik' => null, 'nik_hash' => null];
                }

                $digits = preg_replace('/\D/', '', $value);

                if (strlen($digits) !== 16) {
                    throw new \InvalidArgumentException('NIK harus berupa 16 digit angka.');
                }

                return [
                    'nik' => $this->castAttributeAsEncryptedString('nik', $digits),
                    'nik_hash' => static::nikHash($digits),
                ];
            },
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'extras_id');
    }

    /**
     * RF-06 (perluasan): sampai 4 foto tambahan (foto model/visual sisi lain),
     * di luar foto profil utama. Diurutkan berdasarkan slot (1-4).
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ExtrasPhoto::class)->orderBy('urutan');
    }

    /**
     * RF-13: tanggal shooting dari proyek lain yang statusnya masih aktif
     * (Deal ke atas, belum selesai/batal/ditolak) — dipakai untuk deteksi
     * bentrok jadwal saat extras mau apply ke proyek baru.
     */
    public function activeShootingDates(?int $excludeApplicationId = null): Collection
    {
        return $this->applications()
            ->whereIn('status_partisipasi', ProjectApplication::STATUS_AKTIF)
            ->when($excludeApplicationId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->with('castingProject.shootingDates')
            ->get()
            ->flatMap(fn ($app) => $app->castingProject->shootingDates->pluck('tanggal'))
            ->unique();
    }

    /**
     * RF-08: 3x pembatalan mendadak pada proyek berbeda -> status "melanggar".
     * Dipanggil dari logic Cancellation, bukan dari request user langsung.
     * forceFill() sengaja dipakai di sini (bukan update()) — 'status' memang
     * sengaja tidak masuk $fillable (lihat komentar di atas), jadi update()
     * biasa cuma mendiskard perubahan ini diam-diam tanpa efek apa pun. Bug
     * ini ditemukan & diperbaiki 29 Agu 2026 saat verifikasi trigger RF-08.
     */
    public function recordCancellation(): void
    {
        $this->increment('cancel_count');

        if ($this->cancel_count >= 3) {
            $this->forceFill(['status' => 'melanggar'])->save();
        }
    }

    /**
     * RF-04: dipanggil setelah Extras dinyatakan lolos, sebelum kontrak bisa
     * di-generate (gate di ContractController::show()). Satu-satunya
     * pemanggil sah untuk mengisi nik+rekening di titik ini — format NIK
     * (16 digit) diasumsikan sudah divalidasi form request di controller.
     */
    public function lengkapiKtp(string $nik, ?string $rekening): void
    {
        $hash = static::nikHash(preg_replace('/\D/', '', $nik));

        if (static::where('nik_hash', $hash)->where('id', '!=', $this->id)->exists()) {
            Log::warning('RF-04: percobaan simpan NIK duplikat', ['user_id' => $this->user_id]);

            throw new NikDuplikatException('NIK ini sudah terdaftar di akun lain, hubungi Admin kalau ini kesalahan.');
        }

        $this->nik = $nik;

        if ($rekening) {
            $this->rekening = $rekening;
        }

        $this->save();
    }

    private static function nikHash(string $digits): string
    {
        return hash_hmac('sha256', $digits, config('app.nik_hash_key'));
    }

    /**
     * RF-06: simpan foto profil ke private disk. Menghapus foto lama kalau
     * ada, supaya storage tidak menumpuk file yatim tiap kali Extras ganti foto.
     */
    public function simpanFoto(UploadedFile $file): void
    {
        $this->simpanMedia('foto_profil_path', $file, 'foto');
    }

    /**
     * RF-06: simpan video perkenalan ke private disk. Sama seperti foto,
     * video lama dihapus dulu supaya tidak menumpuk.
     */
    public function simpanVideo(UploadedFile $file): void
    {
        $this->simpanMedia('video_profil_path', $file, 'video');
    }

    private function simpanMedia(string $field, UploadedFile $file, string $folder): void
    {
        if ($this->{$field}) {
            Storage::disk('local')->delete($this->{$field});
        }

        $path = $file->store('extras/'.$this->id.'/'.$folder, 'local');
        $this->update([$field => $path]);
    }

    /**
     * RF-06 (perluasan): simpan/ganti foto tambahan di slot tertentu (1-4).
     * Upload baru ke slot yang sama REPLACE foto lama di slot itu (hapus file
     * fisik lama dulu), bukan menumpuk baris baru — sesuai keputusan "4 slot
     * yang bisa diganti-ganti", bukan galeri tak terbatas.
     */
    public function simpanFotoTambahan(int $slot, UploadedFile $file): void
    {
        abort_unless($slot >= 1 && $slot <= 4, 422, 'Slot foto tidak valid.');

        $existing = $this->photos()->where('urutan', $slot)->first();

        if ($existing) {
            Storage::disk('local')->delete($existing->path);
        }

        $path = $file->store('extras/'.$this->id.'/foto-tambahan', 'local');

        // updateOrCreate supaya aman kalau slot belum ada barisnya sama sekali
        // (belum pernah diisi) maupun sudah ada (replace).
        $this->photos()->updateOrCreate(['urutan' => $slot], ['path' => $path]);
    }

    /**
     * Hapus foto tambahan di slot tertentu (baris + file fisik), tanpa
     * menggeser slot lain — slot yang dihapus jadi kosong lagi, bisa diisi ulang.
     */
    public function hapusFotoTambahan(int $slot): void
    {
        $existing = $this->photos()->where('urutan', $slot)->first();

        if ($existing) {
            Storage::disk('local')->delete($existing->path);
            $existing->delete();
        }
    }
}

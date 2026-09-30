<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\InAppNotification;
use App\Services\WhatsAppService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// 'role' & 'status' TETAP masuk $fillable (whitelist teknis, sama pola
// dengan ExtrasProfile::$foto_profil_path), proteksinya bukan dari sini,
// tapi karena tidak ada route/controller yang nerima 'role'/'status' mentah
// dari $request->all(); RegisterController & AdminManagementController
// selalu set literal/hasil validasi enum, bukan pass-through raw input.
#[Fillable(['name', 'nama_perusahaan', 'email', 'username', 'password', 'wajib_ganti_password', 'role', 'status', 'nomor_wa', 'honor_nominal'])]
// nomor_wa masuk Hidden, bukan super rahasia (bukan NIK/rekening), tapi
// Kebijakan privasi: kontak Extras tidak ditampilkan untuk
// Client; defense-in-depth kalau nanti ada endpoint yang serialize
// User lewat relasi extras.user tanpa sengaja (Client\ReviewController sudah
// eager-load extras.user untuk kirim WA hasil seleksi).
#[Hidden(['password', 'remember_token', 'nomor_wa'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_protected' => 'boolean',
            'wajib_ganti_password' => 'boolean',
        ];
    }

    /**
     * RF-37: normalisasi nomor WA ke format `62xxxxxxxxxx` (tanpa `+`/`0`
     * depan) satu tempat saja, sesuai format yang dipakai whatsapp-web.js
     * (`<nomor>@c.us`), supaya tidak campur-campur di database terlepas
     * format input (08xx, +62xx, 62xx).
     */
    protected function nomorWa(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                if (! $value) {
                    return null;
                }

                $digits = preg_replace('/\D/', '', $value);
                if (str_starts_with($digits, '0')) {
                    return '62'.substr($digits, 1);
                }

                return str_starts_with($digits, '62') ? $digits : '62'.$digits;
            },
        );
    }

    /**
     * BM.1: satu pintu notifikasi. Selalu bikin notif in-app; email/WA opsional, statusnya
     * (`terkirim`/`gagal`/null = antre) disimpan di notifications.data. `kunci` = anti-dobel
     * reminder terjadwal: user yang sudah punya notif dengan kunci sama tidak dikirimi lagi.
     */
    public function kabari(string $judul, string $pesan, ?string $url = null, ?string $jenis = null, ?Mailable $email = null, ?string $wa = null, ?string $kunci = null): bool
    {
        if ($kunci && $this->notifications()->where('data->kunci', $kunci)->exists()) {
            return false;
        }

        $data = array_filter(['jenis' => $jenis, 'kunci' => $kunci]);
        if ($email) {
            try {
                Mail::to($this)->queue($email);
                $data['email'] = 'terkirim';
            } catch (\Throwable) {
                $data['email'] = 'gagal';
            }
        }
        if ($wa !== null) {
            $data['wa'] = null;
        }

        $notif = new InAppNotification($judul, $pesan, $url, $data);
        $notif->id = (string) Str::uuid();
        try {
            $this->notify($notif);
        } catch (\Throwable) {
        }

        if ($wa !== null) {
            app(WhatsAppService::class)->kirimNotifikasi($this, $jenis ?? 'umum', $wa, $notif->id);
        }

        return true;
    }

    public function extrasProfile(): HasOne
    {
        return $this->hasOne(ExtrasProfile::class);
    }

    /** Rule Prune: Extras >30 hari, profil tidak lengkap, 0 pendaftaran. */
    public function scopeMangkrak($query)
    {
        return $query->where('role', self::ROLE_EXTRAS)
            ->where('created_at', '<=', now()->subDays(30))
            ->whereDoesntHave('extrasProfile.applications')
            ->where(fn ($q) => $q->whereDoesntHave('extrasProfile')
                ->orWhereHas('extrasProfile', fn ($ep) => $ep->whereNull('foto_profil_path')->orWhereNull('nik')));
    }

    public function aktivitasTerakhir(): HasOne
    {
        return $this->hasOne(ActivityLog::class)->latestOfMany();
    }

    public function castingProjects(): HasMany
    {
        return $this->hasMany(CastingProject::class, 'admin_id');
    }

    public function adminProjectAssignments(): HasMany
    {
        return $this->hasMany(AdminProjectAssignment::class);
    }

    /** BM.1: proyek milik akun Client ini. */
    public function proyekClient(): HasMany
    {
        return $this->hasMany(CastingProject::class, 'client_id');
    }

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KORLAP = 'korlap';

    public const ROLE_CLIENT = 'client';

    public const ROLE_EXTRAS = 'extras';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_KORLAP,
        self::ROLE_CLIENT,
        self::ROLE_EXTRAS,
    ];

    public const LABELS = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_KORLAP => 'Korlap',
        self::ROLE_CLIENT => 'Client',
        self::ROLE_EXTRAS => 'Extras',
    ];

    public function label(): string
    {
        return self::LABELS[$this->role] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    public function badgeClass(): string
    {
        return 'badge-netral';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKorlap(): bool
    {
        return $this->role === self::ROLE_KORLAP;
    }

    public function isExtras(): bool
    {
        return $this->role === self::ROLE_EXTRAS;
    }

    public function isAnyAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_KORLAP,
        ], true);
    }

    // SPEC BD.6 / D10: godmode Super Admin untuk aksi Admin & Korlap. isAdmin()/isKorlap() tetap murni.
    public function bisaSebagaiAdmin(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function bisaSebagaiKorlap(): bool
    {
        return $this->isKorlap() || $this->isSuperAdmin();
    }

    // Mode Monitoring aktif (admin|korlap) kalau user ini Super Admin; null selain itu.
    public function modeSa(): ?string
    {
        $mode = request()->hasSession() ? request()->session()->get('sa_mode') : null;

        return $this->isSuperAdmin() && in_array($mode, [self::ROLE_ADMIN, self::ROLE_KORLAP], true) ? $mode : null;
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    /**
     * RF-03: satu-satunya sumber kebenaran untuk "role ini dashboard-nya
     * di mana", dipakai LoginController (setelah login), AppServiceProvider
     * (redirect kalau user yang sudah login coba akses /login), dan route
     * /dashboard (pintu masuk universal). Jangan duplikasi mapping ini di
     * tempat lain; kalau ada role baru, cukup ubah di sini.
     */
    public function dashboardUrl(): string
    {
        return match ($this->modeSa() ?? $this->role) {
            self::ROLE_SUPER_ADMIN => '/super-admin/dashboard',
            self::ROLE_ADMIN => '/admin/dashboard',
            self::ROLE_KORLAP => '/admin/absensi',
            self::ROLE_CLIENT => '/client/dashboard',
            self::ROLE_EXTRAS => '/extras/dashboard',
            default => '/login',
        };
    }
}

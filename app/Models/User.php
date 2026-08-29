<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 'role' & 'status' TETAP masuk $fillable (whitelist teknis, sama pola
// dengan ExtrasProfile::$foto_profil_path) — proteksinya bukan dari sini,
// tapi karena tidak ada route/controller yang nerima 'role'/'status' mentah
// dari $request->all(); RegisterController & AdminManagementController
// selalu set literal/hasil validasi enum, bukan pass-through raw input.
#[Fillable(['name', 'email', 'password', 'role', 'status', 'nomor_wa'])]
// nomor_wa masuk Hidden — bukan super rahasia (bukan NIK/rekening), tapi
// tembok anti-poaching (CLAUDE.md §5) taruh kontak Extras di kolom ❌ untuk
// Casting Director; defense-in-depth kalau nanti ada endpoint yang serialize
// User lewat relasi extras.user tanpa sengaja (Cd\ReviewController sudah
// eager-load extras.user untuk kirim WA hasil seleksi).
#[Hidden(['password', 'remember_token', 'nomor_wa'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    /**
     * RF-37: normalisasi nomor WA ke format `62xxxxxxxxxx` (tanpa `+`/`0`
     * depan) satu tempat saja, sesuai format yang dipakai whatsapp-web.js
     * (`<nomor>@c.us`) — supaya tidak campur-campur di database terlepas
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

    public function extrasProfile(): HasOne
    {
        return $this->hasOne(ExtrasProfile::class);
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    public function castingProjects(): HasMany
    {
        return $this->hasMany(CastingProject::class, 'admin_id');
    }

    public function adminProjectAssignments(): HasMany
    {
        return $this->hasMany(AdminProjectAssignment::class);
    }

    public function isExtras(): bool
    {
        return $this->role === 'extras';
    }

    public function isAnyAdmin(): bool
    {
        return in_array($this->role, [
            'super_admin', 'admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed',
        ], true);
    }

    public function isCastingDirector(): bool
    {
        return $this->role === 'casting_director';
    }

    /**
     * RF-03: satu-satunya sumber kebenaran untuk "role ini dashboard-nya
     * di mana" — dipakai LoginController (setelah login), AppServiceProvider
     * (redirect kalau user yang sudah login coba akses /login), dan route
     * /dashboard (pintu masuk universal). Jangan duplikasi mapping ini di
     * tempat lain; kalau ada role baru, cukup ubah di sini.
     */
    public function dashboardUrl(): string
    {
        return match ($this->role) {
            'super_admin' => '/super-admin/dashboard',
            'admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed' => '/admin/dashboard',
            'casting_director' => '/cd/dashboard',
            'extras' => '/extras/dashboard',
            default => '/login',
        };
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// NOTE: 'role' & 'status' SENGAJA tidak masuk sini — diisi lewat proses
// registrasi/admin terkontrol, bukan mass-update dari request user biasa.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
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

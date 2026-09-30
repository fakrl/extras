<?php

namespace App\Support;

use App\Models\Cancellation;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Filter daftar akun bersama: Manajemen Akun SA (BD.4) & Kelola Akun ▸ Extras Admin (BR.1) + export-nya. */
class FilterAkun
{
    public const URUT = ['' => 'Terbaru', 'favorit' => 'Favorit dulu', 'terpilih' => 'Paling sering terpilih', 'batal' => 'Paling sering batal mendadak'];

    public static function dari(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q')),
            'role' => array_key_exists($request->query('role'), User::LABELS) ? $request->query('role') : null,
            'status' => in_array($request->query('status'), ['aktif', 'nonaktif', 'dihapus'], true) ? $request->query('status') : null,
            'sedang_aktif' => $request->boolean('sedang_aktif'),
            'akan_dihapus' => $request->boolean('akan_dihapus'),
            'favorit' => $request->boolean('favorit'),
            'urut' => array_key_exists($u = (string) $request->query('urut'), self::URUT) && $u !== '' ? $u : null,
            'tag' => array_filter(array_map('intval', (array) $request->query('tag', []))),
            'grade' => in_array($request->query('grade'), ['A', 'B', 'C', 'belum'], true) ? $request->query('grade') : null,
        ];
    }

    public static function terapkan(Builder $query, array $f): Builder
    {
        $perProfil = fn ($sub) => $sub->join('extras_profiles', 'extras_profiles.id', '=', 'project_applications.extras_id')
            ->whereColumn('extras_profiles.user_id', 'users.id');

        return $query
            ->when($f['status'] === 'dihapus', fn ($q) => $q->onlyTrashed())
            ->when(in_array($f['status'], ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status', $f['status']))
            ->when($f['role'], fn ($q, $role) => $q->where('role', $role))
            ->when($f['q'] !== '', function ($q) use ($f) {
                $like = "%{$f['q']}%";
                $wa = ltrim(preg_replace('/\D/', '', $f['q']), '0');
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->when(strlen($wa) >= 4, fn ($w) => $w->orWhere('nomor_wa', 'like', "%{$wa}%")));
            })
            ->when($f['sedang_aktif'], fn ($q) => $q->where(fn ($w) => $w
                ->where(fn ($e) => $e->where('role', User::ROLE_EXTRAS)
                    ->whereHas('extrasProfile.applications', fn ($a) => $a->whereNotIn('status_partisipasi', ['ditolak', 'dibatalkan', 'selesai_produksi'])))
                ->orWhere(fn ($s) => $s->whereIn('role', [User::ROLE_ADMIN, User::ROLE_KORLAP])
                    ->whereHas('adminProjectAssignments', fn ($a) => $a->where('status_log', 'berjalan')))
                ->orWhere(fn ($c) => $c->where('role', User::ROLE_CLIENT)
                    ->whereHas('proyekClient', fn ($p) => $p
                        ->where(fn ($t) => $t->diTahap('mendatang'))
                        ->orWhere(fn ($t) => $t->diTahap('berjalan'))))))
            ->when($f['akan_dihapus'], fn ($q) => $q->akanDihapus())
            ->when($f['favorit'], fn ($q) => $q->whereHas('extrasProfile', fn ($p) => $p->where('apresiasi', true)))
            ->when($f['urut'] === 'favorit', fn ($q) => $q->orderByDesc(ExtrasProfile::select('apresiasi')->whereColumn('extras_profiles.user_id', 'users.id')))
            ->when($f['urut'] === 'terpilih', fn ($q) => $q->orderByDesc($perProfil(ProjectApplication::selectRaw('count(*)'))
                ->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)))
            ->when($f['urut'] === 'batal', fn ($q) => $q->orderByDesc($perProfil(Cancellation::selectRaw('count(*)')
                ->join('project_applications', 'project_applications.id', '=', 'cancellations.project_application_id'))
                ->where('cancellations.is_mendadak', true)->where('cancellations.dibatalkan_oleh', 'extras')))
            ->when($f['tag'], function ($q, $tags) {
                foreach ($tags as $id) {
                    $q->whereHas('extrasProfile.categories', fn ($c) => $c->where('extras_categories.id', $id));
                }
            })
            ->when($f['grade'] === 'belum', fn ($q) => $q->where('role', User::ROLE_EXTRAS)
                ->where(fn ($w) => $w->doesntHave('extrasProfile')->orWhereHas('extrasProfile', fn ($p) => $p->whereNull('grade_saat_ini'))))
            ->when(in_array($f['grade'], ['A', 'B', 'C'], true), fn ($q) => $q
                ->whereHas('extrasProfile', fn ($p) => $p->where('grade_saat_ini', $f['grade'])))
            ->latest();
    }
}

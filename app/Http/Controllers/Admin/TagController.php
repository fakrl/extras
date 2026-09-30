<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExtrasCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// SPEC BJ.1: tag bebas, autocomplete + ubah grup/gabung; BR.5: dipanggil dari dialog "Rapikan tag" (JSON) atau form biasa (redirect back).
class TagController extends Controller
{
    public function cari(Request $request): JsonResponse
    {
        $q = mb_strtolower(ExtrasCategory::normalisasi((string) $request->query('q')) ?? '');

        return response()->json(ExtrasCategory::query()
            ->when($q !== '', fn ($w) => $w->whereRaw('LOWER(nama) LIKE ?', ['%'.$q.'%']))
            ->withCount('extrasProfiles')
            ->orderByDesc('extras_profiles_count')->orderBy('nama')
            ->limit(10)->pluck('nama'));
    }

    public function update(Request $request, ExtrasCategory $extrasCategory): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['grup' => ['nullable', Rule::in(array_keys(ExtrasCategory::GRUP))]]);
        $lama = $extrasCategory->grup;
        $extrasCategory->update(['grup' => $data['grup'] ?? null]);

        ActivityLog::record('TAG_UBAH_GRUP', "{$request->user()->label()} {$request->user()->name} memindahkan #{$extrasCategory->nama} dari ".($lama ?? 'Lainnya').' ke '.($extrasCategory->grup ?? 'Lainnya'), $extrasCategory, ['dari' => $lama, 'ke' => $extrasCategory->grup]);

        return $this->hasil($request, "#{$extrasCategory->nama} dipindah ke grup ".($extrasCategory->grup ?? 'Lainnya').'.');
    }

    public function gabung(Request $request, ExtrasCategory $extrasCategory): JsonResponse|RedirectResponse
    {
        $nama = ExtrasCategory::normalisasi((string) $request->input('tujuan')) ?? '';
        $tujuan = ExtrasCategory::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
        if (! $tujuan || $tujuan->is($extrasCategory)) {
            return $this->hasil($request, 'Pilih tag tujuan lain yang sudah ada.', false);
        }

        DB::transaction(function () use ($extrasCategory, $tujuan) {
            $tujuan->extrasProfiles()->syncWithoutDetaching($extrasCategory->extrasProfiles()->pluck('extras_profiles.id'));
            $tujuan->castingProjectClasses()->syncWithoutDetaching($extrasCategory->castingProjectClasses()->pluck('casting_project_classes.id'));
            $extrasCategory->extrasProfiles()->detach();
            $extrasCategory->castingProjectClasses()->detach();
            $extrasCategory->delete();
        });

        ActivityLog::record('TAG_GABUNG', "{$request->user()->label()} {$request->user()->name} menggabung #{$extrasCategory->nama} ke #{$tujuan->nama}", $tujuan, ['asal' => $extrasCategory->nama]);

        return $this->hasil($request, "#{$extrasCategory->nama} digabung ke #{$tujuan->nama}.");
    }

    /** BR.5: hapus tag = lepas dari semua Extras & peran, lalu delete. */
    public function destroy(Request $request, ExtrasCategory $extrasCategory): JsonResponse|RedirectResponse
    {
        $pemakai = $extrasCategory->extrasProfiles()->count();
        DB::transaction(function () use ($extrasCategory) {
            $extrasCategory->extrasProfiles()->detach();
            $extrasCategory->castingProjectClasses()->detach();
            $extrasCategory->delete();
        });

        ActivityLog::record('TAG_HAPUS', "{$request->user()->label()} {$request->user()->name} menghapus #{$extrasCategory->nama} ({$pemakai} Extras)", null, ['nama' => $extrasCategory->nama, 'pemakai' => $pemakai]);

        return $this->hasil($request, "#{$extrasCategory->nama} dihapus.");
    }

    private function hasil(Request $request, string $pesan, bool $ok = true): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['ok' => $ok, 'pesan' => $pesan], $ok ? 200 : 422)
            : back()->with($ok ? 'status' : 'error', $pesan);
    }
}

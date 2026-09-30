<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExtrasCategory;
use App\Support\PerHalaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// SPEC BJ.1: tag bebas, autocomplete + halaman Kelola Tag (ubah grup, gabung).
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

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $grup = (string) $request->query('grup');
        $tags = ExtrasCategory::query()
            ->withCount(['extrasProfiles', 'castingProjectClasses'])
            ->when($q !== '', fn ($w) => $w->whereRaw('LOWER(nama) LIKE ?', ['%'.mb_strtolower($q).'%']))
            ->when($grup === 'Lainnya', fn ($w) => $w->whereNull('grup'))
            ->when(isset(ExtrasCategory::GRUP[$grup]), fn ($w) => $w->where('grup', $grup))
            ->orderByRaw('grup IS NOT NULL')->orderBy('grup')->orderBy('nama')
            ->paginate(PerHalaman::dari($request, 25, PerHalaman::TABEL))
            ->withQueryString();

        $semua = ExtrasCategory::orderBy('nama')->pluck('nama');

        return view('admin.tags.index', compact('tags', 'q', 'grup', 'semua'));
    }

    public function update(Request $request, ExtrasCategory $extrasCategory): RedirectResponse
    {
        $data = $request->validate(['grup' => ['nullable', Rule::in(array_keys(ExtrasCategory::GRUP))]]);
        $lama = $extrasCategory->grup;
        $extrasCategory->update(['grup' => $data['grup'] ?? null]);

        ActivityLog::record('TAG_UBAH_GRUP', "{$request->user()->label()} {$request->user()->name} memindahkan #{$extrasCategory->nama} dari ".($lama ?? 'Lainnya').' ke '.($extrasCategory->grup ?? 'Lainnya'), $extrasCategory, ['dari' => $lama, 'ke' => $extrasCategory->grup]);

        return back()->with('status', "#{$extrasCategory->nama} dipindah ke grup ".($extrasCategory->grup ?? 'Lainnya').'.');
    }

    public function gabung(Request $request, ExtrasCategory $extrasCategory): RedirectResponse
    {
        $nama = ExtrasCategory::normalisasi((string) $request->input('tujuan')) ?? '';
        $tujuan = ExtrasCategory::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
        if (! $tujuan || $tujuan->is($extrasCategory)) {
            return back()->with('error', 'Pilih tag tujuan lain yang sudah ada.');
        }

        DB::transaction(function () use ($extrasCategory, $tujuan) {
            $tujuan->extrasProfiles()->syncWithoutDetaching($extrasCategory->extrasProfiles()->pluck('extras_profiles.id'));
            $tujuan->castingProjectClasses()->syncWithoutDetaching($extrasCategory->castingProjectClasses()->pluck('casting_project_classes.id'));
            $extrasCategory->extrasProfiles()->detach();
            $extrasCategory->castingProjectClasses()->detach();
            $extrasCategory->delete();
        });

        ActivityLog::record('TAG_GABUNG', "{$request->user()->label()} {$request->user()->name} menggabung #{$extrasCategory->nama} ke #{$tujuan->nama}", $tujuan, ['asal' => $extrasCategory->nama]);

        return back()->with('status', "#{$extrasCategory->nama} digabung ke #{$tujuan->nama}.");
    }
}

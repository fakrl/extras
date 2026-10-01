<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PerHalaman;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * BR.2: Kelola Akun ▸ Client untuk Admin, read-only (kelola akun Client = Super Admin, BD.1).
 * Accordion Client → proyek → Extras di-eager-load per halaman (jumlah query tetap, tanpa N+1);
 * client dipaginasi jadi isi accordion terbatas, tidak perlu lazy-load via fetch.
 */
class AkunClientController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $clients = User::query()
            ->where('role', User::ROLE_CLIENT)
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('name', 'like', "%{$q}%")
                ->orWhere('nama_perusahaan', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->withCount('proyekClient')
            ->withMax('proyekClient', 'created_at')
            ->with(['proyekClient' => fn ($p) => $p->latest()->latest('id')->with([
                'shootingDates',
                'applications' => fn ($a) => $a->diajukanKeClient()->with(['reviewClientTerakhir', 'castingProjectClass:id,nama_kelas', 'extras:id,user_id,foto_profil_path', 'extras.user:id,username'])->latest('updated_at'),
            ])])
            ->orderByDesc('proyek_client_max_created_at')->orderBy('name')
            ->paginate(PerHalaman::dari($request, 25, PerHalaman::TABEL))
            ->withQueryString();

        return view('admin.akun.client', ['clients' => $clients, 'q' => $q]);
    }
}

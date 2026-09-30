<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Services\KeuanganService;
use App\Support\PerHalaman;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class CastingProjectController extends Controller
{
    public function index(Request $request, KeuanganService $keuangan)
    {
        $peserta = $request->query('peserta');
        $tahap = array_key_exists((string) $request->query('tahap'), CastingProject::TAHAP) ? $request->query('tahap') : null;
        $bayar = in_array($request->query('bayar'), ['staf', 'extras'], true) ? $request->query('bayar') : null;
        $cari = trim((string) $request->query('q'));
        $tgl = fn (string $k) => rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $request->query($k)), null, false) ?: null;
        $periode = ($dari = $tgl('dari')) && ($sampai = $tgl('sampai')) ? [min($dari, $sampai), max($dari, $sampai)] : null;

        $projects = CastingProject::withCount('applications')
            ->with(['client', 'admin', 'shootingDates', ...KeuanganService::RELASI_CASHFLOW])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($peserta, fn ($q) => $q->whereHas('applications', fn ($a) => $a->where('status_partisipasi', $peserta)))
            ->when($tahap, fn ($q) => $q->diTahap($tahap))
            ->when($request->boolean('tanpa_client'), fn ($q) => $q->whereNull('client_id'))
            ->when($periode, fn ($q) => $q->shootingDalam(...$periode))
            ->when($bayar === 'staf', fn ($q) => $q->whereHas('payrolls', fn ($p) => $p->where('status_bayar', '!=', 'sudah')))
            ->when($bayar === 'extras', fn ($q) => $q->whereHas('payments', fn ($p) => $p->whereNull('ditransfer_at')))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w->where('nama_produksi', 'like', "%{$cari}%")
                ->orWhere('client_ph', 'like', "%{$cari}%")
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$cari}%"))))
            ->when($request->boolean('urgent'), fn ($q) => $q->where(fn ($w) => $w->where('is_urgent', true)
                ->orWhereHas('shootingDates', fn ($d) => $d->whereDate('tanggal', '>=', today())->whereDate('tanggal', '<=', today()->addDays(3)))))
            ->orderByDesc('is_urgent')
            ->latest()
            ->paginate(PerHalaman::dari($request, 24, PerHalaman::KARTU))
            ->withQueryString();

        if ($request->boolean('urgent')) {
            $projects->setCollection($projects->getCollection()->filter(fn ($p) => $p->isUrgent())->values());
        }

        $cashflow = $projects->getCollection()->mapWithKeys(fn ($p) => [$p->id => $keuangan->cashflowProyek($p)]);

        return view('admin.projects.index', compact('projects', 'peserta', 'tahap', 'bayar', 'cari', 'cashflow', 'periode'));
    }

    /**
     * BD.2.4: detail proyek, tab info | pendaftar | cashflow.
     */
    public function show(Request $request, CastingProject $castingProject, KeuanganService $keuangan)
    {
        $tab = in_array($request->query('tab'), ['pendaftar', 'cashflow', 'lampiran'], true) ? $request->query('tab') : 'info';

        $castingProject->load(['client', 'admin', 'shootingDates', 'classes.categories', 'adminAssignments.user']);

        $pendaftar = $tab === 'pendaftar'
            ? $castingProject->applications()
                ->with(['extras' => fn ($q) => $q->withProyekSelesai(), 'extras.user', 'extras.categories', 'castingProjectClass.categories'])
                ->latest()->get()->groupBy('status_partisipasi')
            : collect();

        $cashflow = $tab === 'cashflow' ? $keuangan->cashflowProyek($castingProject) : null;

        return view('admin.projects.show', compact('castingProject', 'tab', 'pendaftar', 'cashflow'));
    }

    public function create()
    {
        return view('admin.projects.create', [
            'tagGroups' => ExtrasCategory::perGrup(),
            'admins' => User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get(),
            'clients' => User::where('role', User::ROLE_CLIENT)->where('status', 'aktif')->orderBy('name')->get(),
        ]);
    }

    /**
     * RF-09: Admin Default membuat proyek casting, nama produksi, kriteria
     * per kelas, kuota, deadline, tanggal-tanggal shooting (jamak, tidak
     * harus berurutan), serta penanda "Butuh Dadakan/Urgent".
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'nama_produksi' => ['required', 'string', 'max:255'],
            'admin_id' => [$user->isSuperAdmin() ? 'required' : 'nullable', 'integer', $this->akunAktif(User::ROLE_ADMIN)],
            // D1: tiap proyek wajib punya 1 akun Client
            'client_id' => ['required', 'integer', $this->akunAktif(User::ROLE_CLIENT)],
            'client_ph' => ['nullable', 'string', 'max:255'],
            'poster_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'wa_group_link' => ['nullable', 'url'],
            'link_grup' => ['nullable', 'url'],
            'deadline' => ['required', 'date'],
            'kuota' => ['required', 'integer', 'min:1'],
            'is_urgent' => ['nullable', 'boolean'],
            'tanggal_shooting' => ['required', 'array', 'min:1'],
            'tanggal_shooting.*' => ['required', 'date'],
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.nama_kelas' => ['required', 'string'],
            'kelas.*.kriteria' => ['nullable', 'string', 'max:500'],
            'kelas.*.budget_client' => ['required', 'numeric', 'min:0'],
            'kelas.*.kuota_kelas' => ['required', 'integer', 'min:1'],
            'kelas.*.jam_callsheet' => ['nullable', 'string'],
            'kelas.*.jam_callingan' => ['nullable', 'string'],
            'kelas.*.karakter' => ['nullable', 'string', 'max:255'],
            'kelas.*.keterangan_scene' => ['nullable', 'string', 'max:255'],
            'kelas.*.tipe_continuity' => ['nullable', 'in:continuity,free'],
            'kelas.*.categories' => ['nullable', 'array'],
            'kelas.*.categories.*' => ['integer', 'exists:extras_categories,id'],
            'kelas.*.tag_nama' => ['nullable', 'array'],
            'kelas.*.tag_nama.*' => ['nullable', 'string', 'max:100'],
        ]);

        $data['kelas'] = $this->tagKelas($data['kelas'], $user);

        $posterPath = $request->hasFile('poster_path')
            ? $request->file('poster_path')->store('posters', 'public')
            : null;

        $coverPath = $request->hasFile('cover_path')
            ? $request->file('cover_path')->store('covers', 'public')
            : null;

        $client = isset($data['client_id']) ? User::find($data['client_id']) : null;

        $project = CastingProject::create([
            'admin_id' => $data['admin_id'] ?? $user->id,
            'client_id' => $client?->id,
            'nama_produksi' => $data['nama_produksi'],
            'client_ph' => ($data['client_ph'] ?? null) ?: ($client->nama_perusahaan ?? null) ?: $client?->name,
            'poster_path' => $posterPath,
            'cover_path' => $coverPath,
            'share_token' => Str::random(32),
            'wa_group_link' => $data['wa_group_link'] ?? null,
            'link_grup' => $data['link_grup'] ?? null,
            'deadline' => $data['deadline'],
            'kuota' => $data['kuota'],
            'is_urgent' => $request->boolean('is_urgent'),
            'status' => 'dibuka',
            'client_request_status' => 'disetujui',
        ]);

        foreach (array_unique($data['tanggal_shooting']) as $tanggal) {
            $project->shootingDates()->create(['tanggal' => $tanggal]);
        }

        foreach ($data['kelas'] as $kelas) {
            if (! empty($kelas['jam_callsheet']) && empty($kelas['jam_callingan'])) {
                $kelas['jam_callingan'] = date('H:i', strtotime($kelas['jam_callsheet'].' -1 hour'));
            }
            $this->simpanKelas($project, $kelas);
        }

        ActivityLog::record('CREATE_PROJECT', "Proyek '{$project->nama_produksi}' dibuat oleh {$user->name} ({$user->label()})", $project);

        return redirect()->route('admin.projects.index')->with('status', 'Proyek casting berhasil dibuat.');
    }

    /**
     * RF-10: Admin Default mengedit proyek casting yang sudah dibuat.
     */
    public function edit(CastingProject $castingProject)
    {
        $castingProject->load('classes.categories', 'shootingDates');

        $applicantsCount = $castingProject->applications()->count();

        return view('admin.projects.edit', [
            ...compact('castingProject', 'applicantsCount'),
            'tagGroups' => ExtrasCategory::perGrup(),
            'admins' => User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get(),
            'clients' => User::where('role', User::ROLE_CLIENT)->where('status', 'aktif')->orderBy('name')->get(),
        ]);
    }

    /**
     * RF-10: kalau proyek sudah punya pendaftar, kelas yang sudah ada TIDAK
     * boleh dihapus (di-update in-place), cegah project_applications
     * (via casting_project_class_id, RF-30) jadi merujuk kelas yang hilang.
     * Kelas baru tetap boleh ditambah. Proyek tanpa pendaftar: delete-recreate
     * biasa, sama seperti store().
     */
    public function update(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $data = $request->validate([
            'nama_produksi' => ['required', 'string', 'max:255'],
            'admin_id' => [$request->user()->isSuperAdmin() ? 'required' : 'nullable', 'integer', $this->akunAktif(User::ROLE_ADMIN)],
            'client_id' => ['required', 'integer', $this->akunAktif(User::ROLE_CLIENT)],
            'client_ph' => ['nullable', 'string', 'max:255'],
            'poster_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'wa_group_link' => ['nullable', 'url'],
            'link_grup' => ['nullable', 'url'],
            'deadline' => ['required', 'date'],
            'kuota' => ['required', 'integer', 'min:1'],
            'is_urgent' => ['nullable', 'boolean'],
            'tanggal_shooting' => ['required', 'array', 'min:1'],
            'tanggal_shooting.*' => ['required', 'date'],
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.id' => ['nullable', 'integer'],
            'kelas.*.nama_kelas' => ['required', 'string'],
            'kelas.*.kriteria' => ['nullable', 'string', 'max:500'],
            'kelas.*.budget_client' => ['required', 'numeric', 'min:0'],
            'kelas.*.kuota_kelas' => ['required', 'integer', 'min:1'],
            'kelas.*.jam_callsheet' => ['nullable', 'string'],
            'kelas.*.jam_callingan' => ['nullable', 'string'],
            'kelas.*.karakter' => ['nullable', 'string', 'max:255'],
            'kelas.*.keterangan_scene' => ['nullable', 'string', 'max:255'],
            'kelas.*.tipe_continuity' => ['nullable', 'in:continuity,free'],
            'kelas.*.categories' => ['nullable', 'array'],
            'kelas.*.categories.*' => ['integer', 'exists:extras_categories,id'],
            'kelas.*.tag_nama' => ['nullable', 'array'],
            'kelas.*.tag_nama.*' => ['nullable', 'string', 'max:100'],
        ], [
            'admin_id.required' => 'Pilih Admin PIC proyek ini.',
            'client_id.required' => 'Pilih akun Client proyek ini dulu (proyek lama belum punya Client).',
        ]);
        $data['kelas'] = $this->tagKelas($data['kelas'], $request->user());

        $hasApplicants = $castingProject->applications()->exists();

        if ($hasApplicants) {
            $existingIds = $castingProject->classes()->pluck('id');
            $submittedIds = collect($data['kelas'])->pluck('id')->filter();

            if ($existingIds->diff($submittedIds)->isNotEmpty()) {
                return back()->withErrors([
                    'kelas' => 'Proyek ini sudah punya pendaftar, kelas yang sudah ada tidak bisa dihapus.',
                ])->withInput();
            }
        }

        // BE.7: client_ph ikut Client baru kalau kosong atau masih nama otomatis Client lama.
        $castingProject->loadMissing('admin', 'client');
        $adminId = (int) ($data['admin_id'] ?? $castingProject->admin_id);
        $client = User::find($data['client_id']);
        $otomatis = fn (?User $c) => $c ? ($c->nama_perusahaan ?: $c->name) : null;
        $clientPh = $data['client_ph'] ?? null;
        if (blank($clientPh) || $clientPh === $otomatis($castingProject->client)) {
            $clientPh = $otomatis($client);
        }

        $perubahan = array_filter([
            (int) $castingProject->admin_id !== $adminId ? 'Admin PIC '.($castingProject->admin?->name ?? '-').' → '.User::find($adminId)->name : null,
            (int) $castingProject->client_id !== $client->id ? 'Client '.($castingProject->client?->name ?? '-').' → '.$client->name.($castingProject->client_id ? ', akses Client lama dicabut' : '') : null,
        ]);

        $updateData = [
            'nama_produksi' => $data['nama_produksi'],
            'admin_id' => $adminId,
            'client_id' => $client->id,
            'client_ph' => $clientPh,
            'wa_group_link' => $data['wa_group_link'] ?? null,
            'link_grup' => $data['link_grup'] ?? null,
            'deadline' => $data['deadline'],
            'kuota' => $data['kuota'],
            'is_urgent' => $request->boolean('is_urgent'),
        ];

        if ($request->hasFile('poster_path')) {
            if ($castingProject->poster_path) {
                Storage::disk('public')->delete($castingProject->poster_path);
            }
            $updateData['poster_path'] = $request->file('poster_path')->store('posters', 'public');
        }

        if ($request->hasFile('cover_path')) {
            if ($castingProject->cover_path) {
                Storage::disk('public')->delete($castingProject->cover_path);
            }
            $updateData['cover_path'] = $request->file('cover_path')->store('covers', 'public');
        }

        $castingProject->update($updateData);

        $tanggalLama = $castingProject->shootingDates()->pluck('tanggal')->map(fn ($t) => Carbon::parse($t)->toDateString());
        $castingProject->shootingDates()->delete();
        foreach (array_unique($data['tanggal_shooting']) as $tanggal) {
            $castingProject->shootingDates()->create(['tanggal' => $tanggal]);
        }
        $castingProject->kabariBentrokJadwal(
            collect($data['tanggal_shooting'])->map(fn ($t) => Carbon::parse($t)->toDateString())->diff($tanggalLama)
        );

        foreach ($data['kelas'] as &$k) {
            if (! empty($k['jam_callsheet']) && empty($k['jam_callingan'])) {
                $k['jam_callingan'] = date('H:i', strtotime($k['jam_callsheet'].' -1 hour'));
            }
        }
        unset($k);

        if (! $hasApplicants) {
            $castingProject->classes()->delete();
        }
        foreach ($data['kelas'] as $kelas) {
            $this->simpanKelas($castingProject, $hasApplicants ? $kelas : Arr::except($kelas, 'id'));
        }

        if ($perubahan) {
            ActivityLog::record('UPDATE_PROJECT_PIC', "Proyek '{$castingProject->nama_produksi}' diubah oleh {$request->user()->name}: ".implode('; ', $perubahan), $castingProject);
        }

        return redirect()->route('admin.projects.index')->with('status', 'Proyek casting berhasil diperbarui.');
    }

    private function akunAktif(string $role): Exists
    {
        return Rule::exists('users', 'id')->where('role', $role)->where('status', 'aktif')->whereNull('deleted_at');
    }

    /** BJ.1: tag peran dari input bebas (nama) + id lama → daftar id di 'categories'. */
    private function tagKelas(array $kelas, User $user): array
    {
        foreach ($kelas as $i => $k) {
            $kelas[$i]['categories'] = ExtrasCategory::idsDariInput($k['tag_nama'] ?? [], $k['categories'] ?? [], $user, "kelas.{$i}.tag_nama");
        }

        return $kelas;
    }

    private function simpanKelas(CastingProject $project, array $kelas): void
    {
        $data = Arr::except($kelas, ['id', 'categories', 'tag_nama']);

        if (empty($kelas['id'])) {
            $class = $project->classes()->create($data);
        } elseif ($class = $project->classes()->find($kelas['id'])) {
            $class->update($data);
        } else {
            return;
        }

        $class->categories()->sync($kelas['categories'] ?? []);
    }

    /**
     * RF-10: Admin Default menutup/membuka proyek casting.
     */
    /** BH.3: kurasi portofolio beranda, cuma proyek selesai. */
    public function updatePortofolio(Request $request, CastingProject $castingProject): RedirectResponse
    {
        abort_unless($request->user()->bisaSebagaiAdmin(), 403);
        if (! $castingProject->bisaPortofolio()) {
            return back()->with('error', 'Portofolio cuma bisa diatur untuk proyek yang sudah selesai.');
        }

        $data = $request->validate([
            'portofolio_judul' => ['nullable', 'string', 'max:150'],
            'portofolio_jenis' => ['nullable', 'string', 'max:80'],
            'portofolio_tahun' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);
        $castingProject->update($data + [
            'tampil_portofolio' => $request->boolean('tampil_portofolio'),
            'tampilkan_nama_client' => $request->boolean('tampilkan_nama_client'),
        ]);

        ActivityLog::record(
            'UPDATE_PORTOFOLIO',
            "{$request->user()->label()} {$request->user()->name} ".($castingProject->tampil_portofolio ? 'menampilkan' : 'menyembunyikan')." proyek '{$castingProject->nama_produksi}' di portofolio beranda",
            $castingProject,
            $castingProject->only(['tampil_portofolio', 'portofolio_judul', 'portofolio_jenis', 'portofolio_tahun', 'tampilkan_nama_client'])
        );

        return back()->with('status', 'Portofolio beranda diperbarui.');
    }

    public function toggleStatus(CastingProject $castingProject): RedirectResponse
    {
        $castingProject->update([
            'status' => $castingProject->status === 'dibuka' ? 'ditutup' : 'dibuka',
        ]);

        return back()->with('status', 'Status proyek diperbarui.');
    }

    /**
     * RF-14/RF-15: Admin Default memfilter pendaftar dan menetapkan Grade.
     */
    public function showApplicants(Request $request, CastingProject $castingProject)
    {
        $grade = $request->query('grade');
        $tab = $request->query('tab');
        $status = array_key_exists($request->query('status', ''), ProjectApplication::LABELS) ? $request->query('status') : null;

        $cdStatuses = ['diajukan_ke_cd', 'direview_cd', 'lolos', 'ditolak'];
        $tagIds = array_map('intval', array_filter((array) $request->query('tag', []), 'is_numeric'));
        $urut = $request->query('urut') === 'cocok' ? 'cocok' : null;
        $cari = trim((string) $request->query('q', ''));

        $applicants = $castingProject->applications()
            ->with([
                'extras' => fn ($q) => $q->withProyekSelesai(),
                'extras.user', 'extras.categories',
                'castingProjectClass.categories', 'fieldNotes.korlap', 'contract', 'payment',
            ])
            ->when($tab === 'cd', fn ($q) => $q->whereIn('status_partisipasi', $cdStatuses))
            ->when($tab !== 'cd' && $grade === 'belum', fn ($q) => $q->whereNull('grade'))
            ->when($tab !== 'cd' && in_array($grade, ['A', 'B', 'C'], true), fn ($q) => $q->where('grade', $grade))
            ->when($tab !== 'cd' && $status, fn ($q) => $q->where('status_partisipasi', $status))
            ->when($tagIds, fn ($q) => $q->whereHas('extras.categories', fn ($c) => $c->whereIn('extras_categories.id', $tagIds)))
            ->when($cari !== '', fn ($q) => $q->where(function ($w) use ($cari) {
                $like = "%{$cari}%";
                $w->whereHas('extras', fn ($e) => $e->where('nama_asli', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('username', 'like', $like)))
                    ->orWhereHas('castingProjectClass', fn ($c) => $c->where('nama_kelas', 'like', $like))
                    ->orWhere('karakter_override', 'like', $like);
            }))
            ->when($urut, fn ($q) => $q->urutPalingCocok())
            ->latest()
            ->paginate(PerHalaman::dari($request, 24, PerHalaman::KARTU))
            ->withQueryString();

        $tagDicari = ExtrasCategory::dicariDiProyek($castingProject->id);
        $tagGroups = ExtrasCategory::perGrup();

        return view('admin.projects.applicants', compact('castingProject', 'applicants', 'grade', 'tab', 'status', 'tagIds', 'urut', 'tagDicari', 'cari', 'tagGroups'));
    }
}

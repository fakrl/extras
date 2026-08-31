<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CastingProjectController extends Controller
{
    public function index()
    {
        $projects = CastingProject::withCount('applications')
            ->orderByDesc('is_urgent')
            ->latest()
            ->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    /**
     * RF-09: Admin Default membuat proyek casting — nama produksi, kriteria
     * per kelas, kuota, deadline, tanggal-tanggal shooting (jamak, tidak
     * harus berurutan), serta penanda "Butuh Dadakan/Urgent".
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_produksi' => ['required', 'string', 'max:255'],
            'client_ph' => ['required', 'string', 'max:255'],
            'wa_group_link' => ['nullable', 'url'],
            'deadline' => ['required', 'date'],
            'kuota' => ['required', 'integer', 'min:1'],
            'is_urgent' => ['nullable', 'boolean'],
            'tanggal_shooting' => ['required', 'array', 'min:1'],
            'tanggal_shooting.*' => ['required', 'date'],
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.nama_kelas' => ['required', 'string'],
            'kelas.*.budget_client' => ['required', 'numeric', 'min:0'],
            'kelas.*.kuota_kelas' => ['required', 'integer', 'min:1'],
        ]);

        $project = $request->user()->castingProjects()->create([
            'nama_produksi' => $data['nama_produksi'],
            'client_ph' => $data['client_ph'],
            'wa_group_link' => $data['wa_group_link'] ?? null,
            'deadline' => $data['deadline'],
            'kuota' => $data['kuota'],
            'is_urgent' => $request->boolean('is_urgent'),
            'status' => 'dibuka',
        ]);

        foreach (array_unique($data['tanggal_shooting']) as $tanggal) {
            $project->shootingDates()->create(['tanggal' => $tanggal]);
        }

        foreach ($data['kelas'] as $kelas) {
            $project->classes()->create($kelas);
        }

        return redirect()->route('admin.projects.index')->with('status', 'Proyek casting berhasil dibuat.');
    }

    /**
     * RF-10: Admin Default mengedit proyek casting yang sudah dibuat.
     */
    public function edit(CastingProject $castingProject)
    {
        $castingProject->load('classes', 'shootingDates');

        $applicantsCount = $castingProject->applications()->count();

        return view('admin.projects.edit', compact('castingProject', 'applicantsCount'));
    }

    /**
     * RF-10: kalau proyek sudah punya pendaftar, kelas yang sudah ada TIDAK
     * boleh dihapus (di-update in-place) — cegah project_applications
     * (via casting_project_class_id, RF-30) jadi merujuk kelas yang hilang.
     * Kelas baru tetap boleh ditambah. Proyek tanpa pendaftar: delete-recreate
     * biasa, sama seperti store().
     */
    public function update(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $data = $request->validate([
            'nama_produksi' => ['required', 'string', 'max:255'],
            'client_ph' => ['required', 'string', 'max:255'],
            'wa_group_link' => ['nullable', 'url'],
            'deadline' => ['required', 'date'],
            'kuota' => ['required', 'integer', 'min:1'],
            'is_urgent' => ['nullable', 'boolean'],
            'tanggal_shooting' => ['required', 'array', 'min:1'],
            'tanggal_shooting.*' => ['required', 'date'],
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.id' => ['nullable', 'integer'],
            'kelas.*.nama_kelas' => ['required', 'string'],
            'kelas.*.budget_client' => ['required', 'numeric', 'min:0'],
            'kelas.*.kuota_kelas' => ['required', 'integer', 'min:1'],
        ]);

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

        $castingProject->update([
            'nama_produksi' => $data['nama_produksi'],
            'client_ph' => $data['client_ph'],
            'wa_group_link' => $data['wa_group_link'] ?? null,
            'deadline' => $data['deadline'],
            'kuota' => $data['kuota'],
            'is_urgent' => $request->boolean('is_urgent'),
        ]);

        $castingProject->shootingDates()->delete();
        foreach (array_unique($data['tanggal_shooting']) as $tanggal) {
            $castingProject->shootingDates()->create(['tanggal' => $tanggal]);
        }

        if ($hasApplicants) {
            foreach ($data['kelas'] as $kelas) {
                $kelasData = Arr::except($kelas, 'id');

                if (! empty($kelas['id'])) {
                    $castingProject->classes()->whereKey($kelas['id'])->update($kelasData);
                } else {
                    $castingProject->classes()->create($kelasData);
                }
            }
        } else {
            $castingProject->classes()->delete();
            foreach ($data['kelas'] as $kelas) {
                $castingProject->classes()->create(Arr::except($kelas, 'id'));
            }
        }

        return redirect()->route('admin.projects.index')->with('status', 'Proyek casting berhasil diperbarui.');
    }

    /**
     * RF-10: Admin Default menutup/membuka proyek casting.
     */
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

        $applicants = $castingProject->applications()
            ->with('extras', 'extras.user', 'extras.photos', 'fieldNotes.korlap')
            ->when($grade === 'belum', fn ($q) => $q->whereNull('grade'))
            ->when(in_array($grade, ['A', 'B', 'C'], true), fn ($q) => $q->where('grade', $grade))
            ->latest()
            ->get();

        return view('admin.projects.applicants', compact('castingProject', 'applicants', 'grade'));
    }
}

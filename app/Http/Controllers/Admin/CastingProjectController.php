<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
     * RF-10: Admin Default mengedit atau menutup proyek casting.
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
    public function showApplicants(CastingProject $castingProject)
    {
        $applicants = $castingProject->applications()
            ->with('extras', 'extras.photos')
            ->latest()
            ->get();

        return view('admin.projects.applicants', compact('castingProject', 'applicants'));
    }
}

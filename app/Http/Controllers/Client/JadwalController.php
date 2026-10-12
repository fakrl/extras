<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    private function guardProject(CastingProject $project): void
    {
        abort_unless($project->milikClient(Auth::user()), 403);
        abort_unless($project->client_request_status === 'disetujui', 404);
    }

    public function index()
    {
        $projects = CastingProject::query()->milikClient(Auth::user())->where('client_request_status', 'disetujui')
            ->with('shootingDates')
            ->latest()
            ->get();

        return view('client.jadwal.index', compact('projects'));
    }

    public function show(CastingProject $project)
    {
        $this->guardProject($project);

        $project->load(['shootingDates', 'shootingDates.attendances.projectApplication.extras.user:id,username']);

        return view('client.jadwal.show', compact('project'));
    }

    public function store(Request $request, CastingProject $project)
    {
        $this->guardProject($project);

        $request->merge(['panggilan' => collect($request->input('panggilan', []))
            ->filter(fn ($p) => filled($p['nama'] ?? null) || filled($p['jam'] ?? null))->values()->all()]);

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
            'catatan' => ['nullable', 'string'],
            'panggilan' => ['nullable', 'array'],
            'panggilan.*.nama' => ['required', 'string', 'max:255'],
            'panggilan.*.jam' => ['required', 'string', 'max:10'],
        ], [
            'panggilan.*.nama.required' => 'Nama karakter/kategori pada Daftar Panggilan wajib diisi, atau hapus barisnya.',
            'panggilan.*.jam.required' => 'Jam panggil pada Daftar Panggilan wajib diisi, atau hapus barisnya.',
        ]);

        $panggilan = $data['panggilan'] ?? [];

        $tanggal = $project->shootingDates()->whereDate('tanggal', $data['tanggal'])->first();
        if (! $tanggal) {
            return back()->with('error', 'Tanggal itu bukan tanggal shooting proyek ini. Hubungi JBTB untuk menambah tanggal.');
        }

        $tanggal->update(
            [
                'lokasi' => $data['lokasi'] ?? null,
                'jam_mulai' => $data['jam_mulai'] ?? null,
                'jam_selesai' => $data['jam_selesai'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'panggilan' => $panggilan ?: null,
            ]
        );

        return redirect()->route('client.jadwal.show', $project)->with('success', 'Jadwal disimpan.');
    }
}

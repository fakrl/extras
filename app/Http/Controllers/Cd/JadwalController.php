<?php

namespace App\Http\Controllers\Cd;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    private function guardProject(CastingProject $project): void
    {
        abort_unless($project->milikClient(Auth::user()), 403);
    }

    public function index()
    {
        $projects = CastingProject::query()->milikClient(Auth::user())
            ->with('shootingDates')
            ->latest()
            ->get();

        return view('cd.jadwal.index', compact('projects'));
    }

    public function show(CastingProject $project)
    {
        $this->guardProject($project);

        $project->load(['shootingDates', 'shootingDates.attendances']);

        return view('cd.jadwal.show', compact('project'));
    }

    public function store(Request $request, CastingProject $project)
    {
        $this->guardProject($project);

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
            'catatan' => ['nullable', 'string'],
            'panggilan' => ['nullable', 'array'],
            'panggilan.*.nama' => ['required_with:panggilan', 'string', 'max:255'],
            'panggilan.*.jam' => ['required_with:panggilan', 'string', 'max:10'],
        ]);

        // Filter out empty panggilan rows sent from the form
        $panggilan = collect($data['panggilan'] ?? [])->filter(fn ($p) => ! empty($p['nama']))->values()->all();

        $tanggalBaru = ! $project->shootingDates()->whereDate('tanggal', $data['tanggal'])->exists();

        EventShootingDate::updateOrCreate(
            ['casting_project_id' => $project->id, 'tanggal' => $data['tanggal']],
            [
                'lokasi' => $data['lokasi'] ?? null,
                'jam_mulai' => $data['jam_mulai'] ?? null,
                'jam_selesai' => $data['jam_selesai'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'panggilan' => $panggilan ?: null,
            ]
        );

        if ($tanggalBaru) {
            $project->kabariBentrokJadwal([$data['tanggal']]);
        }

        return redirect()->route('cd.jadwal.show', $project)->with('success', 'Jadwal disimpan.');
    }
}

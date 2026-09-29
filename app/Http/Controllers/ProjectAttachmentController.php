<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectAttachmentController extends Controller
{
    public static function bolehAkses(User $user, CastingProject $project): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin()
            || ($user->isClient() && ((int) $project->client_id === $user->id
                || $project->cdAssignments()->where('cd_user_id', $user->id)->exists()));
    }

    public function store(Request $request, CastingProject $castingProject): RedirectResponse
    {
        abort_unless(self::bolehAkses($request->user(), $castingProject), 403);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ProjectAttachment::RULE_FILE,
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        ProjectAttachment::unggah($castingProject, $data['files'], $request->user(), $data['keterangan'] ?? null);

        return back()->with('status', count($data['files']).' lampiran diunggah.');
    }

    public function download(Request $request, ProjectAttachment $projectAttachment): StreamedResponse
    {
        abort_unless(self::bolehAkses($request->user(), $projectAttachment->castingProject), 403);

        return Storage::disk('local')->download($projectAttachment->path, $projectAttachment->nama_asli);
    }

    public function destroy(Request $request, ProjectAttachment $projectAttachment): RedirectResponse
    {
        $user = $request->user();
        $project = $projectAttachment->castingProject;
        abort_unless(self::bolehAkses($user, $project)
            && ($user->isSuperAdmin() || (int) $projectAttachment->uploaded_by === $user->id), 403, 'Hanya pengunggah atau Super Admin yang bisa menghapus lampiran ini.');

        Storage::disk('local')->delete($projectAttachment->path);
        $projectAttachment->delete();

        ActivityLog::record(
            'PROJECT_ATTACHMENT_DELETED',
            "Lampiran '{$projectAttachment->nama_asli}' dihapus dari proyek '{$project->nama_produksi}'",
            $project
        );

        return back()->with('status', 'Lampiran dihapus.');
    }
}

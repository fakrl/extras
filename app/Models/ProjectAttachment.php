<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

#[Fillable(['casting_project_id', 'uploaded_by', 'nama_asli', 'path', 'mime', 'ukuran', 'keterangan'])]
class ProjectAttachment extends Model
{
    public const RULE_FILE = ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'];

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ukuranLabel(): string
    {
        return $this->ukuran >= 1048576
            ? number_format($this->ukuran / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->ukuran / 1024)).' KB';
    }

    /** @param  UploadedFile[]  $files */
    public static function unggah(CastingProject $project, array $files, User $user, ?string $keterangan = null): void
    {
        foreach ($files as $file) {
            $project->attachments()->create([
                'uploaded_by' => $user->id,
                'nama_asli' => $file->getClientOriginalName(),
                'path' => $file->store("project-attachments/{$project->id}", 'local'),
                'mime' => $file->getClientMimeType(),
                'ukuran' => $file->getSize(),
                'keterangan' => $keterangan,
            ]);
        }

        ActivityLog::record(
            'PROJECT_ATTACHMENT_UPLOADED',
            count($files)." lampiran diunggah ke proyek '{$project->nama_produksi}'",
            $project,
            ['files' => array_map(fn ($f) => $f->getClientOriginalName(), $files)],
            $user
        );
    }
}

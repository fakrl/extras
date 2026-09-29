<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id', 'role', 'action', 'subject_type', 'subject_id',
    'description', 'properties', 'ip_address', 'user_agent', 'created_at',
])]
class ActivityLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public const ACTION_LABELS = [
        'APPLY_PROJECT' => 'Daftar lowongan',
        'APPROVE_PROJECT_REQUEST' => 'ACC pengajuan proyek',
        'REJECT_PROJECT_REQUEST' => 'Tolak pengajuan proyek',
        'SUBMIT_PROJECT_REQUEST' => 'Ajukan proyek',
        'CONFIRM_PAYMENT' => 'Konfirmasi pembayaran',
        'DISPUTE_PAYMENT' => 'Sengketakan pembayaran',
        'UPLOAD_PAYOUT_TRANSFER' => 'Unggah bukti transfer',
        'STAFF_PAYROLL_PAID' => 'Bayar honor staf',
        'VALIDATE_ATTENDANCE' => 'Validasi absensi',
        'REJECT_ATTENDANCE' => 'Tolak absensi',
        'SUBMIT_SELFIE_ATTENDANCE' => 'Absen selfie',
        'SIGN_CONTRACT' => 'TTD kontrak',
        'SIGN_INVOICE' => 'TTD invoice',
        'UPLOAD_CUSTOM_INVOICE_DOC' => 'Unggah dokumen invoice',
        'REVIEW_CANDIDATE_REJECT' => 'Tolak kandidat',
        'SET_EXTRAS_GRADE' => 'Atur grade Extras',
        'UPDATE_EXTRAS_KATEGORI' => 'Ubah kategori Extras',
        'TOGGLE_EXTRAS_BERANDA' => 'Atur Extras di beranda',
        'UPDATE_LINEUP_BREAKDOWN' => 'Ubah lineup',
        'UPDATE_USER' => 'Ubah akun',
        'TOGGLE_USER_STATUS' => 'Aktif/nonaktifkan akun',
        'RESET_USER_PASSWORD' => 'Reset password',
        'PRUNE_ABANDONED_USERS' => 'Bersihkan akun mangkrak',
        'SA_MODE_MULAI' => 'Mulai mode Monitoring',
        'SA_MODE_KELUAR' => 'Keluar mode Monitoring',
    ];

    public static function actionLabel(string $code): string
    {
        return self::ACTION_LABELS[$code] ?? ucfirst(strtolower(str_replace(['_', '.'], ' ', $code)));
    }

    public function subjectUrl(): ?string
    {
        $s = $this->subject;

        return match (true) {
            $s instanceof User => route('super-admin.admins.show', $s),
            $s instanceof ExtrasProfile => route('super-admin.admins.show', $s->user_id),
            $s instanceof CastingProject => route('admin.projects.show', $s),
            $s instanceof ProjectApplication => route('admin.projects.applicants', $s->casting_project_id),
            default => null,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat aktivitas baru ke audit trail sistem.
     */
    public static function record(
        string $action,
        string $description,
        mixed $subject = null,
        array $properties = [],
        ?User $user = null
    ): self {
        $actor = $user ?? auth()->user();

        if ($sebagai = $actor?->modeSa()) {
            $properties['sebagai'] = $sebagai;
            $description .= ' (sebagai '.User::LABELS[$sebagai].')';
        }

        return static::create([
            'user_id' => $actor?->id,
            'role' => $actor?->role ?? 'system',
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'description' => $description,
            'properties' => ! empty($properties) ? $properties : null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}

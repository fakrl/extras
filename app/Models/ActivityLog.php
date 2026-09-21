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

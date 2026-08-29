<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'channel', 'jenis', 'status', 'sent_at'])]
class NotificationLog extends Model
{
    public $table = 'notifications_log';

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RF-36/RF-37: catat setiap percobaan kirim notifikasi, terlepas dari
     * sukses/gagal. Dipanggil dari titik trigger di model/controller terkait.
     * $channel default 'email' supaya semua pemanggil existing (email) tetap
     * jalan tanpa perlu diubah.
     */
    public static function catat(int $userId, string $jenis, bool $terkirim, string $channel = 'email'): void
    {
        static::create([
            'user_id' => $userId,
            'channel' => $channel,
            'jenis' => $jenis,
            'status' => $terkirim ? 'terkirim' : 'gagal',
            'sent_at' => now(),
        ]);
    }
}

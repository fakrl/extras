<?php

namespace App\Http\Controllers;

use App\Notifications\InAppNotification;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }

    /** EX-26: tandai dibaca lalu lanjut ke tautan notifikasi. */
    public function baca(string $id): RedirectResponse
    {
        $notif = auth()->user()->notifications()->findOrFail($id);
        $notif->markAsRead();
        $url = InAppNotification::relatif($notif->data['url'] ?? null);

        return redirect($url && ! str_starts_with($url, '//') ? $url : '/');
    }
}

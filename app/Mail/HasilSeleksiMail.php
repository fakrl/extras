<?php

namespace App\Mail;

use App\Models\ProjectApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HasilSeleksiMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectApplication $application) {}

    public function build(): self
    {
        $lolos = $this->application->status_partisipasi === 'lolos';

        return $this->subject($lolos ? 'Selamat, Kamu Lolos Seleksi!' : 'Hasil Seleksi Casting')
            ->view('emails.hasil-seleksi')
            ->with([
                'lolos' => $lolos,
                'namaProduksi' => $this->application->castingProject->nama_produksi,
                'alasan' => $this->application->alasan_tolak,
            ]);
    }
}

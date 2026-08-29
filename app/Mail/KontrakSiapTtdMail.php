<?php

namespace App\Mail;

use App\Models\ProjectApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KontrakSiapTtdMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectApplication $application) {}

    public function build(): self
    {
        return $this->subject('Kontrak Siap Ditandatangani')
            ->view('emails.kontrak-siap-ttd')
            ->with([
                'namaProduksi' => $this->application->castingProject->nama_produksi,
            ]);
    }
}

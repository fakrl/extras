<?php

namespace App\Mail;

use App\Models\FeeNegotiation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KonfirmasiFeeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public FeeNegotiation $negotiation) {}

    public function build(): self
    {
        return $this->subject('Ada Penawaran Fee Baru')
            ->view('emails.konfirmasi-fee')
            ->with([
                'namaProduksi' => $this->negotiation->projectApplication->castingProject->nama_produksi,
                'nominal' => $this->negotiation->nominal,
                'diajukanOleh' => $this->negotiation->diajukan_oleh,
            ]);
    }
}

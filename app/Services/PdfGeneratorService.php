<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Satu infrastruktur PDF yang di-reuse untuk 3 jenis dokumen: kontrak
 * (Talent Release), invoice, dan slip honor staf — sesuai keputusan di
 * TECH-STACK.md, bukan 3 generator terpisah. Semua disimpan di private
 * disk (bukan public), sesuai SECURITY-CHECKLIST.md poin 16.
 */
class PdfGeneratorService
{
    public function generate(string $view, array $data, string $storagePath): string
    {
        $pdf = Pdf::loadView($view, $data);

        Storage::disk('local')->put($storagePath, $pdf->output());

        return $storagePath;
    }
}

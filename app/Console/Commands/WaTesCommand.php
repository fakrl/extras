<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class WaTesCommand extends Command
{
    protected $signature = 'wa:tes {nomor} {pesan?}';

    protected $description = 'BO.1: kirim WA tes langsung ke Node service (tanpa queue)';

    public function handle(WhatsAppService $whatsapp): int
    {
        $pesan = $this->argument('pesan') ?? 'Tes WhatsApp dari '.config('app.name').' ('.now()->format('d M Y H:i').').';

        try {
            $res = $whatsapp->kirimLangsung($this->argument('nomor'), $pesan);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (ConnectionException $e) {
            $this->error('Koneksi ditolak ke '.config('services.whatsapp.url').' — Node whatsapp-service belum jalan?');

            return self::FAILURE;
        }

        if ($res->successful()) {
            $this->info('Terkirim.');

            return self::SUCCESS;
        }

        $this->error(match ($res->status()) {
            401 => '401: token salah — WHATSAPP_SERVICE_TOKEN di .env Laravel & whatsapp-service/.env harus sama persis.',
            503 => '503: WhatsApp belum siap — scan QR dulu di terminal Node.',
            500 => '500: Node gagal kirim pesan (nomor tidak terdaftar WA / sesi putus).',
            default => "HTTP {$res->status()}",
        }.' Pesan Node: '.($res->json('pesan') ?? $res->body()));

        return self::FAILURE;
    }
}

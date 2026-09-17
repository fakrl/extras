<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RF-37: reminder H-1 shooting, jam 08:00 WIB tiap hari.
Schedule::command('reminder:h1-shooting')->dailyAt('08:00')->timezone('Asia/Jakarta');

// L.3: reminder ke CD saat H-3/H-1 masih ada kandidat belum dipilih.
Schedule::command('reminder:h3-pilih-extras')->dailyAt('07:30')->timezone('Asia/Jakarta');

// L.3: reminder ke CD saat H-3 shooting tapi jadwal detail belum diisi.
Schedule::command('reminder:input-jadwal')->dailyAt('07:45')->timezone('Asia/Jakarta');

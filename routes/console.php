<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RF-37: reminder H-1 shooting, jam 08:00 WIB tiap hari.
Schedule::command('reminder:h1-shooting')->dailyAt('08:00')->timezone('Asia/Jakarta');

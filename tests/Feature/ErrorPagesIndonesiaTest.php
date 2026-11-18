<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesIndonesiaTest extends TestCase
{
    public function test_halaman_429_bahasa_indonesia(): void
    {
        Route::get('/__test-abort-429', fn () => abort(429));
        $this->get('/__test-abort-429')->assertStatus(429)->assertSee('Terlalu banyak percobaan', false);
    }

    public function test_halaman_401_bahasa_indonesia(): void
    {
        Route::get('/__test-abort-401', fn () => abort(401));
        $this->get('/__test-abort-401')->assertStatus(401)->assertSee('masuk (login) dulu', false);
    }

    public function test_halaman_405_bahasa_indonesia(): void
    {
        Route::get('/__test-abort-405', fn () => abort(405));
        $this->get('/__test-abort-405')->assertStatus(405)->assertSee('tidak didukung', false);
    }
}

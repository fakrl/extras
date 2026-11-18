<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationMessageCapitalTest extends TestCase
{
    use RefreshDatabase;

    public function test_pesan_wajib_diisi_huruf_pertama_kapital(): void
    {
        $pesan = trans('validation.required', ['attribute' => trans('validation.attributes.email')]);

        $this->assertSame('Email wajib diisi.', $pesan);
    }

    public function test_login_gagal_validasi_tampilkan_pesan_kapital(): void
    {
        $response = $this->post('/login', ['email' => '', 'password' => '']);
        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringStartsWith('Email', $errors[0]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example. RF-55: '/' naik dari closure jadi
     * HomeController::index() (query count casting_projects), jadi butuh
     * DB bermigrasi sekarang — RefreshDatabase ditambahkan di sini.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_page_returns_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Layanan Publik Warga');
    }

    public function test_register_page_returns_successful_response(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Warga');
    }
}

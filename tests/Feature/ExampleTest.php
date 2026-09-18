<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root membutuhkan autentikasi, jadi pengunjung anonim diarahkan ke halaman login.
     */
    public function test_pengunjung_anonim_diarahkan_ke_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_halaman_login_dapat_diakses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}

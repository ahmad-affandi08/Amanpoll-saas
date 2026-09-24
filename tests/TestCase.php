<?php

namespace Tests;

use App\Shared\Infrastructure\Keamanan\PenyelesaiDns;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Dukungan\PenyelesaiDnsPalsu;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Penjaga URL keluar meresolusi DNS; test tidak pernah boleh menyentuh DNS sungguhan.
        $this->app->instance(PenyelesaiDns::class, new PenyelesaiDnsPalsu);
    }

    /** Resolver palsu test ini, untuk memetakan host ke alamat tertentu. */
    protected function dnsPalsu(): PenyelesaiDnsPalsu
    {
        $dns = $this->app->make(PenyelesaiDns::class);
        $this->assertInstanceOf(PenyelesaiDnsPalsu::class, $dns);

        return $dns;
    }
}

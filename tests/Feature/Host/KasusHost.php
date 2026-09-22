<?php

declare(strict_types=1);

namespace Tests\Feature\Host;

use App\Core\Host\PetaHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Host diambil dari konfigurasi, tidak pernah dari nama produksi, sesuai MARKETING.md 1.3. */
abstract class KasusHost extends TestCase
{
    use RefreshDatabase;

    protected PetaHost $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->host = app(PetaHost::class);
    }

    protected function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) $this->host->publik().'/'.ltrim($path, '/');
    }

    protected function urlDashboard(string $path = '/'): string
    {
        return 'http://'.$this->host->dashboard().'/'.ltrim($path, '/');
    }
}

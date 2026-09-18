<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (glob(app_path('Domain/*/routes.php')) ?: [] as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }
    }
}

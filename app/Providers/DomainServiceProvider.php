<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Host\PetaHost;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    /** Seluruh rute domain adalah rute sistem, jadi host dashboard dipasang di sini sekali untuk semuanya. */
    public function boot(): void
    {
        Route::domain(app(PetaHost::class)->dashboard())->group(function (): void {
            foreach (glob(app_path('Domain/*/routes.php')) ?: [] as $routeFile) {
                $this->loadRoutesFrom($routeFile);
            }
        });
    }
}

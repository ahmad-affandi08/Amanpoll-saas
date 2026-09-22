<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Host\PetaHost;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    /**
     * Seluruh rute domain adalah rute sistem, jadi host dashboard dipasang di
     * sini sekali untuk semuanya. Bila tiap berkas rute domain memasangnya
     * sendiri, satu berkas baru yang lupa melakukannya akan diam-diam terbuka
     * di host publik (PRD 5.4).
     */
    public function boot(): void
    {
        Route::domain(app(PetaHost::class)->dashboard())->group(function (): void {
            foreach (glob(app_path('Domain/*/routes.php')) ?: [] as $routeFile) {
                $this->loadRoutesFrom($routeFile);
            }
        });
    }
}

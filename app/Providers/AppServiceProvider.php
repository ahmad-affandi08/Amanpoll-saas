<?php

namespace App\Providers;

use App\Shared\Infrastructure\Inertia\FeaturePageViewFinder;
use App\Shared\Infrastructure\Keamanan\PenjagaKonfigurasiProduksi;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('inertia.view-finder', function ($app) {
            return new FeaturePageViewFinder(
                $app['files'],
                $app['config']->get('inertia.pages.paths'),
                $app['config']->get('inertia.pages.extensions')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inertia props tidak butuh amplop 'data' ala API; frontend memakai prop resource langsung.
        JsonResource::withoutWrapping();

        PenjagaKonfigurasiProduksi::periksa($this->app);
    }
}

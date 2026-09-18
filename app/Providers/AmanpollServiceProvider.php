<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Support\ServiceProvider;

final class AmanpollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KonteksOrganisasi::class, fn () => new KonteksOrganisasi());
        $this->app->bind(
            \App\Shared\Domain\Contracts\TransaksiDatabase::class,
            \App\Shared\Infrastructure\Persistence\TransaksiDatabaseLaravel::class,
        );
    }

    public function boot(): void
    {
        $zonaWaktu = (string) config('amanpoll.zona_waktu_default', 'Asia/Jakarta');
        config(['app.timezone' => $zonaWaktu]);
        date_default_timezone_set($zonaWaktu);

        // Binding repository spesifik domain ditambahkan ketika use-case mulai diimplementasikan.
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Audit\KorelasiId;
use App\Core\Entitas\RegistriEntitas;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Support\ServiceProvider;

final class AmanpollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KonteksOrganisasi::class, fn () => new KonteksOrganisasi());
        $this->app->scoped(KorelasiId::class, fn () => new KorelasiId());
        $this->app->singleton(RegistriEntitas::class);
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

        // Entitas yang boleh dilampiri berkas/tag/kolom kustom/komentar (FASE 05).
        // Modul domain baru mendaftarkan entitasnya sendiri di sini saat dibangun.
        $registri = $this->app->make(RegistriEntitas::class);
        $registri->daftarkan('UnitOrganisasi', UnitOrganisasi::class, 'Pengaturan.Kelola');
        $registri->daftarkan('Lokasi', Lokasi::class, 'Pengaturan.Kelola');
    }
}

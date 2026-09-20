<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Audit\KorelasiId;
use App\Core\Entitas\RegistriEntitas;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Infrastructure\Listeners\SinkronkanStatusPersetujuanSiklusAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Infrastructure\Persistence\TransaksiDatabaseLaravel;
use Illuminate\Support\ServiceProvider;

final class AmanpollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KonteksOrganisasi::class, fn () => new KonteksOrganisasi);
        $this->app->scoped(KorelasiId::class, fn () => new KorelasiId);
        $this->app->singleton(RegistriEntitas::class);
        $this->app->bind(
            TransaksiDatabase::class,
            TransaksiDatabaseLaravel::class,
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
        $registri->daftarkan('Penyedia', Penyedia::class, 'Penyedia.Kelola');
        $registri->daftarkan('Aset', Aset::class, 'Aset.Ubah');
        $registri->daftarkan('GaransiAset', GaransiAset::class, 'Aset.Ubah');
        $registri->daftarkan('PermintaanMutasiAset', PermintaanMutasiAset::class, 'Aset.Ubah');
        $registri->daftarkan('SerahTerimaAset', SerahTerimaAset::class, 'Aset.Ubah');
        $registri->daftarkan('PengajuanPenghapusanAset', PengajuanPenghapusanAset::class, 'Aset.Hapus');
        $registri->daftarkan('Keluhan', Keluhan::class, 'Keluhan.Kelola');
        $registri->daftarkan('PerintahKerja', PerintahKerja::class, 'PerintahKerja.Kelola');

        // Mesin Persetujuan (FASE 06) domain-agnostic; SiklusAset menyalin
        // balik hasil keputusan ke status entitasnya sendiri lewat observer.
        PermintaanPersetujuan::observe(SinkronkanStatusPersetujuanSiklusAset::class);
    }
}

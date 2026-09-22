<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Listeners;

use App\Domain\Langganan\Domain\Events\PeristiwaLangganan;
use App\Domain\Pemasaran\Application\Actions\KonversiTrial;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;

/**
 * Menyalin peristiwa revenue ke taxonomy pemasaran (MARKETING.md 23, 33.05).
 *
 * Arah ketergantungannya sengaja satu arah: Langganan menyiarkan peristiwanya
 * dan tidak tahu ada yang mendengarkan, sehingga domain penagihan tidak pernah
 * bergantung pada modul pemasaran.
 */
final class CatatPeristiwaRevenue
{
    private const PETA = [
        PeristiwaLangganan::LANGGANAN_DIBUAT => KatalogPeristiwaPemasaran::LANGGANAN_DIBUAT,
        PeristiwaLangganan::LANGGANAN_DIBATALKAN => KatalogPeristiwaPemasaran::LANGGANAN_DIBATALKAN,
        PeristiwaLangganan::PEMBAYARAN_BERHASIL => KatalogPeristiwaPemasaran::PEMBAYARAN_BERHASIL,
        PeristiwaLangganan::PEMBAYARAN_GAGAL => KatalogPeristiwaPemasaran::PEMBAYARAN_GAGAL,
        PeristiwaLangganan::UPGRADE_DILAKUKAN => KatalogPeristiwaPemasaran::UPGRADE_DILAKUKAN,
        PeristiwaLangganan::DOWNGRADE_DILAKUKAN => KatalogPeristiwaPemasaran::DOWNGRADE_DILAKUKAN,
    ];

    public function __construct(
        private readonly PerekamEventPemasaran $event,
        private readonly KonversiTrial $konversi,
    ) {}

    public function handle(PeristiwaLangganan $peristiwa): void
    {
        $jenis = self::PETA[$peristiwa->kode] ?? null;

        if ($jenis === null) {
            return;
        }

        $trial = Trial::query()->where('OrganisasiId', $peristiwa->organisasiId)->first();

        $this->event->catat(
            $jenis,
            pengenalPengunjung: $trial?->PengenalPengunjung,
            dataTambahan: [...$peristiwa->data, 'LanggananId' => $peristiwa->langgananId],
            organisasiId: $peristiwa->organisasiId,
        );

        if ($peristiwa->kode === PeristiwaLangganan::PEMBAYARAN_BERHASIL && $trial !== null) {
            $this->konversi->jalankan($trial, $peristiwa->langgananId);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Listeners;

use App\Domain\Langganan\Domain\Events\PeristiwaLangganan;
use App\Domain\Pemasaran\Application\Actions\KonversiTrial;
use App\Domain\Pemasaran\Application\Services\PelacakReferral;
use App\Domain\Pemasaran\Application\Services\PenghitungKomisiPartner;
use App\Domain\Pemasaran\Application\Services\PenghitungRewardReferral;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Pemasaran\Jobs\ProsesRewardReferral;

/** Menyalin peristiwa revenue ke taxonomy pemasaran; Langganan menyiarkan tanpa tahu ada yang mendengar (MARKETING.md 23, 33.05). */
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
        private readonly PelacakReferral $referral,
        private readonly PenghitungRewardReferral $reward,
        private readonly PenghitungKomisiPartner $komisi,
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

        if ($peristiwa->kode !== PeristiwaLangganan::PEMBAYARAN_BERHASIL) {
            return;
        }

        if ($trial !== null) {
            $this->konversi->jalankan($trial, $peristiwa->langgananId);
        }

        $this->bayarkanReferral($peristiwa);
        $this->komisikanPartner($peristiwa);
    }

    /**
     * Komisi partner lahir dari id pembayarannya, bukan dari jumlah yang ikut di
     * muatan peristiwa: domain Langganan yang memastikan pembayarannya nyata.
     */
    private function komisikanPartner(PeristiwaLangganan $peristiwa): void
    {
        $this->komisi->tandaiLeadLunas($peristiwa->organisasiId);

        $pembayaranId = $peristiwa->data['PembayaranId'] ?? null;

        if (is_string($pembayaranId) && $pembayaranId !== '') {
            $this->komisi->dariPembayaran($pembayaranId);
        }
    }

    /** Pembayaran pertama itulah yang mengubah referral menjadi imbalan. */
    private function bayarkanReferral(PeristiwaLangganan $peristiwa): void
    {
        $referral = $this->referral->tandaiPaid($peristiwa->organisasiId, $peristiwa->langgananId);

        if ($referral === null) {
            return;
        }

        $imbalan = $this->reward->terbitkan($referral);

        if ($imbalan !== null) {
            ProsesRewardReferral::dispatch($imbalan->Id);
        }
    }
}

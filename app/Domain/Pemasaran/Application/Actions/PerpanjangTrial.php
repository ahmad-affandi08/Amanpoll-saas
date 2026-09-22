<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Pemasaran\Application\Services\PembacaKonfigurasiTrial;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Memperpanjang trial sesuai extension policy (MARKETING.md 12).
 *
 * Langganannya dimutasi lewat KelolaLangganan, tidak pernah disentuh langsung
 * dari sini: kolom UjiCobaSampai milik domain Langganan, beserta auditnya.
 */
final class PerpanjangTrial
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PembacaKonfigurasiTrial $konfigurasi,
        private readonly PindahkanStatusTrial $pindahkanStatus,
        private readonly KelolaLangganan $kelolaLangganan,
        private readonly LayananLangganan $layananLangganan,
    ) {}

    public function jalankan(Trial $trial, int $hari, ?string $alasan = null): Trial
    {
        if ($hari < 1) {
            throw new AturanBisnisDilanggar('Perpanjangan trial minimal satu hari.');
        }

        $maks = $this->konfigurasi->berlaku()->perpanjanganMaksHari;
        $total = $trial->HariPerpanjangan + $hari;

        if ($total > $maks) {
            throw new AturanBisnisDilanggar(
                "Perpanjangan trial dibatasi {$maks} hari; permintaan ini menjadikannya {$total} hari.",
            );
        }

        return $this->transaksi->jalankan(function () use ($trial, $hari, $total, $alasan): Trial {
            $trial = $this->pindahkanStatus->jalankan($trial, StatusTrial::Diperpanjang, $alasan);

            $trial->HariPerpanjangan = $total;
            $trial->BerakhirPada = $trial->BerakhirPada->addDays($hari);
            $trial->save();

            $langganan = $this->layananLangganan->untukOrganisasi($trial->OrganisasiId);

            if ($langganan !== null) {
                $this->kelolaLangganan->perpanjangUjiCoba($langganan, $hari);
            }

            return $trial;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Langganan\Domain\Contracts\PemberiImbalanLangganan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Hanya perpanjangan yang punya tempat di Billing hari ini; bentuk lain ditolak terang-terangan, bukan diterima lalu diam (MARKETING.md 20). */
final class PemberiImbalanLanggananBawaan implements PemberiImbalanLangganan
{
    public const PERPANJANGAN = 'Perpanjangan';

    public function __construct(
        private readonly LayananLangganan $layanan,
        private readonly KelolaLangganan $kelola,
    ) {}

    public function mendukung(string $jenisImbalan): bool
    {
        return $jenisImbalan === self::PERPANJANGAN;
    }

    /** @param array<string, mixed> $rincian */
    public function beri(string $organisasiId, string $jenisImbalan, float $nilai, array $rincian = []): string
    {
        if (! $this->mendukung($jenisImbalan)) {
            throw new AturanBisnisDilanggar(
                "Billing belum dapat memberikan imbalan berbentuk {$jenisImbalan}.",
            );
        }

        $hari = (int) round($nilai);

        if ($hari < 1) {
            throw new AturanBisnisDilanggar('Perpanjangan imbalan minimal satu hari.');
        }

        $langganan = $this->layanan->untukOrganisasi($organisasiId);

        if ($langganan === null) {
            throw new AturanBisnisDilanggar('Organisasi ini tidak punya langganan untuk diperpanjang.');
        }

        $this->kelola->perpanjangUjiCoba($langganan, $hari);

        return "Langganan {$langganan->Id} diperpanjang {$hari} hari.";
    }
}

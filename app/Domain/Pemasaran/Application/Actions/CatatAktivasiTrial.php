<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ButirAktivasiTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Carbon\CarbonImmutable;

/**
 * Menandai satu butir activation checklist (MARKETING.md 12, 23).
 *
 * Idempoten: butir yang sudah selesai tidak ditandai ulang, sehingga peristiwa
 * "aset pertama dibuat" tetap berarti aset pertama meski asetnya seratus.
 */
final class CatatAktivasiTrial
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PerekamEventPemasaran $event,
        private readonly PindahkanStatusTrial $pindahkanStatus,
    ) {}

    public function jalankan(string $organisasiId, ButirAktivasi $butir): ?Trial
    {
        $trial = Trial::query()->with('butir')->where('OrganisasiId', $organisasiId)->first();

        if ($trial === null || ! $trial->Status->berjalan()) {
            return $trial;
        }

        if (in_array($butir->value, $trial->butirSelesai(), true)) {
            return $trial;
        }

        return $this->transaksi->jalankan(function () use ($trial, $butir, $organisasiId): Trial {
            ButirAktivasiTrial::create([
                'TrialId' => $trial->Id,
                'Butir' => $butir,
                'SelesaiPada' => CarbonImmutable::now(),
            ]);

            $peristiwa = $butir->peristiwa();

            if ($peristiwa !== null) {
                $this->event->catat(
                    $peristiwa,
                    pengenalPengunjung: $trial->PengenalPengunjung,
                    dataTambahan: ['TrialId' => $trial->Id],
                    organisasiId: $organisasiId,
                );
            }

            return $this->majukanStatus($trial->fresh(['butir']) ?? $trial, $organisasiId);
        });
    }

    private function majukanStatus(Trial $trial, string $organisasiId): Trial
    {
        if ($trial->seluruhButirWajibSelesai() && $trial->Status !== StatusTrial::Teraktivasi) {
            $trial = $this->pindahkanStatus->jalankan($trial, StatusTrial::Teraktivasi);

            $this->event->catat(
                KatalogPeristiwaPemasaran::TRIAL_TERAKTIVASI,
                pengenalPengunjung: $trial->PengenalPengunjung,
                dataTambahan: ['TrialId' => $trial->Id],
                organisasiId: $organisasiId,
            );

            return $trial;
        }

        if ($trial->Status === StatusTrial::Setup) {
            return $this->pindahkanStatus->jalankan($trial, StatusTrial::Aktif);
        }

        return $trial;
    }
}

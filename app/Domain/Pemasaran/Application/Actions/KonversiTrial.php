<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\PenghitungSkorProspek;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Menutup trial sebagai konversi pada pembayaran pertama yang berhasil
 * (MARKETING.md 12, 33.05).
 *
 * Idempoten: pembayaran kedua tidak mengonversi ulang trial yang sudah menang.
 */
final class KonversiTrial
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PindahkanStatusTrial $pindahkanStatus,
        private readonly PindahkanTahapProspek $pindahkanTahap,
        private readonly PenghitungSkorProspek $skor,
    ) {}

    public function jalankan(Trial $trial, ?string $langgananId = null): Trial
    {
        if ($trial->Status === StatusTrial::Konversi) {
            return $trial;
        }

        return $this->transaksi->jalankan(function () use ($trial, $langgananId): Trial {
            $trial = $this->pindahkanStatus->jalankan($trial, StatusTrial::Konversi);

            if ($langgananId !== null && $trial->LanggananId === null) {
                $trial->LanggananId = $langgananId;
                $trial->save();
            }

            $this->menangkanProspek($trial);

            return $trial;
        });
    }

    /** Prospeknya ikut pindah ke tahap menang, dan skornya dihitung ulang. */
    private function menangkanProspek(Trial $trial): void
    {
        $prospek = $trial->prospek;

        if ($prospek === null) {
            return;
        }

        $menang = TahapPipeline::query()->where('Kode', KatalogTahapPipeline::MENANG)->first();

        if ($menang !== null && $prospek->TahapPipelineId !== $menang->Id) {
            $this->pindahkanTahap->jalankan($prospek, $menang, 'Trial dikonversi menjadi langganan berbayar.');
        }

        $this->skor->hitungUlang($prospek);
    }
}

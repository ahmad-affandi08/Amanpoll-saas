<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiHalaman;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Menerbitkan satu versi halaman ke situs publik (MARKETING.md 8). */
final class TerbitkanHalaman
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly PenyimpanIsiHalaman $isi,
    ) {}

    public function jalankan(HalamanPemasaran $halaman, ?VersiHalamanPemasaran $versi = null): HalamanPemasaran
    {
        return $this->transaksi->jalankan(function () use ($halaman, $versi): HalamanPemasaran {
            $versi ??= $halaman->versiDraf;

            if ($versi === null) {
                throw new AturanBisnisDilanggar('Halaman belum punya versi yang dapat diterbitkan.');
            }

            if ($versi->HalamanPemasaranId !== $halaman->Id) {
                throw new AturanBisnisDilanggar('Versi tersebut bukan milik halaman ini.');
            }

            $statusSebelum = $halaman->Status;

            if (! $statusSebelum->bolehPindahKe(StatusHalamanPemasaran::Terbit)) {
                throw new AturanBisnisDilanggar(
                    "Halaman berstatus {$statusSebelum->value} tidak dapat langsung diterbitkan.",
                );
            }

            $versiSebelum = $halaman->VersiTerbitId;

            $halaman->VersiTerbitId = $versi->Id;
            $halaman->Status = StatusHalamanPemasaran::Terbit;
            $halaman->TerbitPada = CarbonImmutable::now();
            // Jadwal tarik yang sudah lewat tidak diwariskan ke penerbitan baru.
            if ($halaman->TarikPada !== null && $halaman->TarikPada->isPast()) {
                $halaman->TarikPada = null;
            }
            $halaman->save();

            $this->isi->buang($halaman->Slug);

            $this->audit->catat(
                'HalamanPemasaran.Diterbitkan',
                'HalamanPemasaran',
                $halaman->Id,
                dataSebelum: ['Status' => $statusSebelum->value, 'VersiTerbitId' => $versiSebelum],
                dataSesudah: [
                    'Status' => StatusHalamanPemasaran::Terbit->value,
                    'VersiTerbitId' => $versi->Id,
                    'Nomor' => $versi->Nomor,
                    'Slug' => $halaman->Slug,
                ],
            );

            return $halaman;
        });
    }
}

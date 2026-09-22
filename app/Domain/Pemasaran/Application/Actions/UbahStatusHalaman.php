<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiHalaman;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Perpindahan status halaman selain penerbitan (MARKETING.md 8). */
final class UbahStatusHalaman
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly PenyimpanIsiHalaman $isi,
    ) {}

    public function jalankan(
        HalamanPemasaran $halaman,
        StatusHalamanPemasaran $tujuan,
        ?CarbonImmutable $terbitPada = null,
        ?CarbonImmutable $tarikPada = null,
    ): HalamanPemasaran {
        return $this->transaksi->jalankan(
            function () use ($halaman, $tujuan, $terbitPada, $tarikPada): HalamanPemasaran {
                if ($tujuan === StatusHalamanPemasaran::Terbit) {
                    throw new AturanBisnisDilanggar('Penerbitan dilakukan lewat aksi TerbitkanHalaman.');
                }

                $sebelum = $halaman->Status;

                if (! $sebelum->bolehPindahKe($tujuan)) {
                    throw new AturanBisnisDilanggar(
                        "Halaman tidak dapat berpindah dari {$sebelum->value} ke {$tujuan->value}.",
                    );
                }

                if ($tujuan === StatusHalamanPemasaran::Terjadwal) {
                    $this->pastikanJadwalMasukAkal($halaman, $terbitPada, $tarikPada);
                    $halaman->TerbitPada = $terbitPada;
                    $halaman->TarikPada = $tarikPada;
                }

                $halaman->Status = $tujuan;
                $halaman->save();

                // Halaman yang tidak lagi terbit harus hilang dari situs publik seketika.
                if ($sebelum === StatusHalamanPemasaran::Terbit) {
                    $this->isi->buang($halaman->Slug);
                }

                $this->audit->catat(
                    'HalamanPemasaran.StatusDiubah',
                    'HalamanPemasaran',
                    $halaman->Id,
                    dataSebelum: ['Status' => $sebelum->value],
                    dataSesudah: [
                        'Status' => $tujuan->value,
                        'Slug' => $halaman->Slug,
                        'TerbitPada' => $halaman->TerbitPada?->toIso8601String(),
                        'TarikPada' => $halaman->TarikPada?->toIso8601String(),
                    ],
                );

                return $halaman;
            },
        );
    }

    private function pastikanJadwalMasukAkal(
        HalamanPemasaran $halaman,
        ?CarbonImmutable $terbitPada,
        ?CarbonImmutable $tarikPada,
    ): void {
        if ($terbitPada === null) {
            throw new AturanBisnisDilanggar('Halaman terjadwal harus punya waktu terbit.');
        }

        if ($tarikPada !== null && $tarikPada->lessThanOrEqualTo($terbitPada)) {
            throw new AturanBisnisDilanggar('Waktu tarik harus setelah waktu terbit.');
        }

        // Tanpa versi, penjadwal akan menerbitkan halaman kosong pada jam yang sudah diumumkan.
        if ($halaman->VersiDrafId === null && $halaman->VersiTerbitId === null) {
            throw new AturanBisnisDilanggar('Halaman belum punya versi yang dapat dijadwalkan.');
        }
    }
}

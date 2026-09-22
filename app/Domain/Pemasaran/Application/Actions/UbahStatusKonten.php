<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiKonten;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Perpindahan status konten selain penerbitan (MARKETING.md 9). */
final class UbahStatusKonten
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly PenyimpanIsiKonten $isi,
    ) {}

    public function jalankan(KontenPemasaran $konten, StatusHalamanPemasaran $tujuan): KontenPemasaran
    {
        return $this->transaksi->jalankan(function () use ($konten, $tujuan): KontenPemasaran {
            if ($tujuan === StatusHalamanPemasaran::Terbit) {
                throw new AturanBisnisDilanggar('Penerbitan dilakukan lewat aksi TerbitkanKonten.');
            }

            $sebelum = $konten->Status;

            if (! $sebelum->bolehPindahKe($tujuan)) {
                throw new AturanBisnisDilanggar(
                    "Konten tidak dapat berpindah dari {$sebelum->value} ke {$tujuan->value}.",
                );
            }

            $konten->Status = $tujuan;
            $konten->save();

            // Konten yang tidak lagi terbit harus hilang dari situs publik seketika.
            if ($sebelum === StatusHalamanPemasaran::Terbit) {
                $this->isi->buang($konten->Slug);
            }

            $this->audit->catat(
                'KontenPemasaran.StatusDiubah',
                'KontenPemasaran',
                $konten->Id,
                dataSebelum: ['Status' => $sebelum->value],
                dataSesudah: ['Status' => $tujuan->value, 'Slug' => $konten->Slug],
            );

            return $konten;
        });
    }
}

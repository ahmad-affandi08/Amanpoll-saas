<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Services;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class LayananSaldoAnggaran
{
    /**
     * @return array{jumlah: string, terpakai: string, ditahan: string, sisa: string}
     */
    public function hitung(PosAnggaran $posAnggaran): array
    {
        $agregat = TransaksiAnggaran::query()
            ->where('PosAnggaranId', $posAnggaran->Id)
            ->selectRaw("COALESCE(SUM(CASE WHEN Jenis IN ('Realisasi', 'Penyesuaian') THEN Jumlah ELSE 0 END), 0) AS Terpakai")
            ->selectRaw("COALESCE(SUM(CASE WHEN Jenis = 'Komitmen' THEN Jumlah WHEN Jenis = 'PelepasanKomitmen' THEN -Jumlah ELSE 0 END), 0) AS Ditahan")
            ->first();

        $jumlah = Uang::dariString((string) $posAnggaran->Jumlah);
        $terpakai = Uang::dariString((string) ($agregat?->getAttribute('Terpakai') ?? '0'));
        $ditahan = Uang::dariString((string) ($agregat?->getAttribute('Ditahan') ?? '0'));

        if ($terpakai->nilaiMinor() < 0 || $ditahan->nilaiMinor() < 0) {
            throw new AturanBisnisDilanggar('Ledger anggaran tidak valid dan harus direkonsiliasi oleh administrator.');
        }

        $sisa = $jumlah->kurang($terpakai)->kurang($ditahan);

        return [
            'jumlah' => $jumlah->keString(),
            'terpakai' => $terpakai->keString(),
            'ditahan' => $ditahan->keString(),
            'sisa' => $sisa->keString(),
        ];
    }

    /**
     * Kolom Terpakai dan Ditahan hanyalah proyeksi; ledger tetap sumber kebenaran.
     *
     * @return array{jumlah: string, terpakai: string, ditahan: string, sisa: string}
     */
    public function rekonsiliasi(PosAnggaran $posAnggaran): array
    {
        $saldo = $this->hitung($posAnggaran);

        $posAnggaran->forceFill([
            'Terpakai' => $saldo['terpakai'],
            'Ditahan' => $saldo['ditahan'],
        ])->save();

        return $saldo;
    }
}

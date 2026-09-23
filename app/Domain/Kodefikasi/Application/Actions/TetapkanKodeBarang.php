<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Penomoran\Services\LayananKodeOtomatis;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarang;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarangAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Menetapkan kode barang ke satu aset dan mengalokasikan NUP-nya.
 *
 * Idempotent per (aset, standar): menetapkan ulang kode yang sama tidak
 * mengubah apa pun, termasuk NUP-nya. Mengganti ke kode barang lain
 * menerbitkan NUP baru, karena NUP berurut per kode barang -- nomor lama
 * tidak berlaku di bawah kode yang berbeda.
 */
final class TetapkanKodeBarang
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananKodeOtomatis $penomoran,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    public function jalankan(Aset $aset, KodeBarang $kodeBarang): KodeBarangAset
    {
        return $this->transaksi->jalankan(function () use ($aset, $kodeBarang): KodeBarangAset {
            $adaSebelumnya = KodeBarangAset::query()
                ->where('AsetId', $aset->Id)
                ->where('Standar', $kodeBarang->Standar->value)
                ->first();

            if ($adaSebelumnya !== null && $adaSebelumnya->KodeBarangId === $kodeBarang->Id) {
                return $adaSebelumnya;
            }

            $nup = $this->nupBerikutnya($kodeBarang);

            if ($adaSebelumnya !== null) {
                $adaSebelumnya->update(['KodeBarangId' => $kodeBarang->Id, 'Nup' => $nup]);

                return $adaSebelumnya;
            }

            return KodeBarangAset::create([
                'AsetId' => $aset->Id,
                'KodeBarangId' => $kodeBarang->Id,
                'Standar' => $kodeBarang->Standar->value,
                'Nup' => $nup,
            ]);
        });
    }

    /** Penghitung dipisah per kode barang, sesuai cara NUP dinomori. */
    private function nupBerikutnya(KodeBarang $kodeBarang): int
    {
        return $this->penomoran->nomorBerikutnya(
            'Nup:'.$kodeBarang->Standar->value.':'.$kodeBarang->Kode,
            $this->konteks->wajibId(),
        );
    }
}

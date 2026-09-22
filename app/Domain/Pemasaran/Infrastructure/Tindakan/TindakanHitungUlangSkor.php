<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Services\PenghitungSkorProspek;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;

/** Menghitung ulang skor dari sinyal yang ada, bukan menambah angka bebas ke dalamnya. */
final class TindakanHitungUlangSkor implements TindakanOtomasi
{
    public function __construct(private readonly PenghitungSkorProspek $penghitung) {}

    public function kode(): string
    {
        return 'HitungUlangSkor';
    }

    public function label(): string
    {
        return 'Hitung ulang skor';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return [];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $skor = $this->penghitung->hitungUlang($konteks->wajibProspek());

        return "Skor prospek menjadi {$skor}.";
    }
}

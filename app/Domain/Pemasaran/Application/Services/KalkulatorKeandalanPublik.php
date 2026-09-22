<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pelaporan\Domain\ValueObjects\RumusKeandalan;
use App\Domain\Pemasaran\Domain\ValueObjects\HasilKalkulatorKeandalan;

/**
 * Kalkulator publik MTTR, MTBF, dan downtime. Rumusnya dipinjam dari
 * RumusKeandalan, tempat KPI di dalam aplikasi menghitung angka yang sama,
 * supaya tidak pernah ada dua rumus yang kelak berbeda (MARKETING.md 10, KatalogKpi 21.01).
 */
final class KalkulatorKeandalanPublik
{
    public const TANPA_KEGAGALAN = 'Belum ada kegagalan yang dimasukkan, jadi MTTR dan MTBF belum punya penyebut.';

    public const TANPA_WAKTU_OPERASIONAL = 'Jumlah aset atau panjang rentang masih nol, jadi tidak ada waktu operasional yang diukur.';

    public const DOWNTIME_MELAMPAUI = 'Menit downtime melampaui seluruh waktu operasional; periksa kembali angkanya.';

    private const MENIT_PER_HARI = 1440;

    public function hitung(
        int $jumlahAset,
        int $hariRentang,
        int $jumlahKegagalan,
        int $menitDowntime,
    ): HasilKalkulatorKeandalan {
        $adaWaktuOperasional = $jumlahAset > 0 && $hariRentang > 0;
        $menitOperasional = $adaWaktuOperasional
            ? RumusKeandalan::menitOperasional($jumlahAset, $hariRentang * self::MENIT_PER_HARI)
            : 0.0;

        $adaKegagalan = $jumlahKegagalan > 0;

        return new HasilKalkulatorKeandalan(
            menitOperasional: $menitOperasional,
            totalJam: RumusKeandalan::totalJam($menitDowntime),
            mttr: $adaKegagalan ? RumusKeandalan::mttr($menitDowntime, $jumlahKegagalan) : null,
            mtbf: $adaKegagalan && $adaWaktuOperasional
                ? RumusKeandalan::mtbf($menitOperasional, $menitDowntime, $jumlahKegagalan)
                : null,
            ketersediaan: $adaWaktuOperasional
                ? RumusKeandalan::ketersediaan($menitOperasional, $menitDowntime)
                : null,
            alasanMttr: $adaKegagalan ? null : self::TANPA_KEGAGALAN,
            alasanMtbf: $this->alasanMtbf($adaKegagalan, $adaWaktuOperasional),
            alasanKetersediaan: $adaWaktuOperasional ? null : self::TANPA_WAKTU_OPERASIONAL,
            peringatan: $adaWaktuOperasional && $menitDowntime > $menitOperasional
                ? self::DOWNTIME_MELAMPAUI
                : null,
        );
    }

    private function alasanMtbf(bool $adaKegagalan, bool $adaWaktuOperasional): ?string
    {
        if (! $adaKegagalan) {
            return self::TANPA_KEGAGALAN;
        }

        return $adaWaktuOperasional ? null : self::TANPA_WAKTU_OPERASIONAL;
    }
}

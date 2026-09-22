<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\ValueObjects;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Satu-satunya tempat downtime, ketersediaan, MTTR, dan MTBF dihitung (KatalogKpi 21.01).
 * Seluruhnya atas angka mentah, supaya kalkulator publik memakai rumus yang sama
 * dengan KPI di dalam aplikasi, bukan salinannya.
 */
final class RumusKeandalan
{
    /** Menit operasional = jumlah aset dikali panjang rentang. */
    public static function menitOperasional(int $jumlahAset, int $menitRentang): float
    {
        return (float) $jumlahAset * max(1, $menitRentang);
    }

    public static function totalJam(int $menitDowntime): float
    {
        return round($menitDowntime / 60, 1);
    }

    /** Downtime tidak boleh melampaui waktu operasional dan membuat waktu aktif negatif. */
    public static function menitAktif(float $menitOperasional, int $menitDowntime): float
    {
        return max(0.0, $menitOperasional - $menitDowntime);
    }

    /** Tanpa kegagalan, MTTR tidak punya arti; penyebut nol ditolak, bukan dijawab nol. */
    public static function mttr(int $menitDowntime, int $jumlahKegagalan): float
    {
        self::pastikanAdaKegagalan($jumlahKegagalan);

        return round($menitDowntime / $jumlahKegagalan / 60, 1);
    }

    public static function mtbf(float $menitOperasional, int $menitDowntime, int $jumlahKegagalan): float
    {
        self::pastikanAdaKegagalan($jumlahKegagalan);

        return round(self::menitAktif($menitOperasional, $menitDowntime) / $jumlahKegagalan / 60, 1);
    }

    /** Tanpa waktu operasional tidak ada yang diukur, jadi bukan pula "100% tersedia". */
    public static function ketersediaan(float $menitOperasional, int $menitDowntime): float
    {
        if ($menitOperasional <= 0.0) {
            throw new AturanBisnisDilanggar('Ketersediaan butuh waktu operasional lebih dari nol.');
        }

        return round(self::menitAktif($menitOperasional, $menitDowntime) / $menitOperasional * 100, 1);
    }

    private static function pastikanAdaKegagalan(int $jumlahKegagalan): void
    {
        if ($jumlahKegagalan < 1) {
            throw new AturanBisnisDilanggar('Rumus keandalan butuh setidaknya satu kegagalan.');
        }
    }
}

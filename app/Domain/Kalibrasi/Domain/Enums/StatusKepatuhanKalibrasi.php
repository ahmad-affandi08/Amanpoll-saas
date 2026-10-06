<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Domain\Enums;

use Carbon\CarbonInterface;

/**
 * Status jatuh tempo sebuah rencana kalibrasi pada satu hari tertentu.
 *
 * Satu-satunya tempat aturannya ditulis: daftar rencana, detail, dasbor, hitungan
 * kepatuhan, dan pengingat harian memakainya, jadi angka di satu layar tidak dapat
 * berbeda dari layar lain.
 */
enum StatusKepatuhanKalibrasi: string
{
    case Valid = 'Valid';
    case SegeraJatuhTempo = 'SegeraJatuhTempo';
    case Terlambat = 'Terlambat';
    case TidakAktif = 'TidakAktif';

    /**
     * @param  CarbonInterface  $hariIni  Tanggal hari ini di organisasi (KalenderOrganisasi::hariIni).
     */
    public static function untuk(
        bool $aktif,
        CarbonInterface $tanggalBerikutnya,
        int $peringatanHariSebelum,
        CarbonInterface $hariIni,
    ): self {
        return match (true) {
            ! $aktif => self::TidakAktif,
            $tanggalBerikutnya->lt($hariIni) => self::Terlambat,
            $tanggalBerikutnya->lte($hariIni->copy()->addDays($peringatanHariSebelum)) => self::SegeraJatuhTempo,
            default => self::Valid,
        };
    }
}

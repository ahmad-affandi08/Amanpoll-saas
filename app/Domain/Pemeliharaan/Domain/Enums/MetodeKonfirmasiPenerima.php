<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Enums;

/** Tiga cara penerima mengonfirmasi pekerjaan (PRD 8.22). */
enum MetodeKonfirmasiPenerima: string
{
    /** Pelapor keluhan asal, dari akunnya sendiri di Mode Lapangan. */
    case Pelapor = 'Pelapor';

    /** Penerima memindai QR bertoken dari HP teknisi, lalu mengonfirmasi dari akunnya. */
    case PindaiQr = 'PindaiQr';

    /** Penerima tanpa akun menandatangani di HP teknisi; boleh tanpa sinyal. */
    case TandaTanganPerangkat = 'TandaTanganPerangkat';

    public function label(): string
    {
        return match ($this) {
            self::Pelapor => 'Pelapor dari akunnya',
            self::PindaiQr => 'Pindai QR di lokasi',
            self::TandaTanganPerangkat => 'Tanda tangan di HP teknisi',
        };
    }
}

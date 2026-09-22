<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/**
 * Status halaman pemasaran (MARKETING.md 8).
 *
 * Peta transisinya ditulis di sini, bukan di controller, karena halaman dapat
 * berpindah status lewat tiga jalur berbeda — tombol di konsol, penjadwal, dan
 * rollback — dan ketiganya harus tunduk pada aturan yang sama.
 */
enum StatusHalamanPemasaran: string
{
    case Draf = 'Draf';
    case Review = 'Review';
    case Terjadwal = 'Terjadwal';
    case Terbit = 'Terbit';
    case Diarsipkan = 'Diarsipkan';

    /** @return list<self> */
    public function tujuanYangDiizinkan(): array
    {
        return match ($this) {
            self::Draf => [self::Review, self::Terjadwal, self::Terbit, self::Diarsipkan],
            self::Review => [self::Draf, self::Terjadwal, self::Terbit, self::Diarsipkan],
            self::Terjadwal => [self::Draf, self::Terbit, self::Diarsipkan],
            // Terbit ke Terbit adalah penerbitan ulang: draf baru menggantikan
            // versi yang sedang tayang. Itu justru operasi yang paling sering
            // terjadi, jadi ia harus lewat jalur yang sama dan bukan lewat
            // penurunan status lebih dulu.
            self::Terbit => [self::Draf, self::Terbit, self::Diarsipkan],
            // Halaman yang diarsipkan kembali sebagai draf, tidak pernah
            // langsung terbit: isinya sudah lama tidak ditinjau siapa pun.
            self::Diarsipkan => [self::Draf],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanYangDiizinkan(), true);
    }

    public function terlihatPublik(): bool
    {
        return $this === self::Terbit;
    }
}

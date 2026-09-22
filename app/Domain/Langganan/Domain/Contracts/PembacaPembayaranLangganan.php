<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Contracts;

use App\Domain\Langganan\Domain\ValueObjects\PembayaranTerkonfirmasi;

/**
 * Satu-satunya pintu domain lain menanyakan apakah sebuah pembayaran sungguh terjadi.
 *
 * Muatan peristiwa dapat dikarang siapa pun yang dapat menyiarkan event; yang
 * menentukan lahirnya komisi adalah baris pembayaran di domain Langganan, bukan
 * angka yang ikut menumpang di peristiwanya (MARKETING.md 21).
 */
interface PembacaPembayaranLangganan
{
    /** Null bila pembayarannya tidak ada, belum berhasil, atau belum punya tanggal bayar. */
    public function konfirmasi(string $pembayaranId): ?PembayaranTerkonfirmasi;
}

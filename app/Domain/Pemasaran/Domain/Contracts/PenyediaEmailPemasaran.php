<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

use App\Domain\Pemasaran\Domain\ValueObjects\PesanEmail;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanPenyedia;

/** Abstraksi penyedia email pemasaran (MARKETING.md 15, 28). */
interface PenyediaEmailPemasaran
{
    public function kode(): string;

    /** Mengembalikan pengenal pesan di sisi penyedia, dipakai untuk menyinkronkan statusnya nanti. */
    public function kirim(PesanEmail $pesan): string;

    /**
     * Status terkini beberapa kiriman sekaligus.
     *
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanPenyedia>
     */
    public function statusKiriman(array $idPesan): array;
}

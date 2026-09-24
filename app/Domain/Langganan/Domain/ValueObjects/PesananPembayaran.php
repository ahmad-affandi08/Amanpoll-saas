<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Semua yang dibutuhkan penyedia untuk membuka satu sesi bayar tagihan (PRD 8.23).
 *
 * Jumlahnya rupiah bulat: gateway Indonesia tidak menerima pecahan rupiah.
 */
final readonly class PesananPembayaran
{
    public function __construct(
        public string $idPesanan,
        public string $nomorTagihan,
        public int $jumlah,
        public string $deskripsi,
        public string $namaPelanggan,
        public string $emailPelanggan,
        public ?string $teleponPelanggan,
        public string $urlKembali,
        public string $urlNotifikasi,
        public CarbonImmutable $kedaluwarsaPada,
        public int $menitBerlaku,
    ) {}
}

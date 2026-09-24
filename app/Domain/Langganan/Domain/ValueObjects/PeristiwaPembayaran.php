<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;

/** Bentuk baku sebuah peristiwa pembayaran, apa pun penyedianya (22.06). */
final readonly class PeristiwaPembayaran
{
    /**
     * @param  string  $idPeristiwa  Identitas peristiwa di sisi penyedia; inilah
     *                               kunci idempotensi, jadi ia wajib stabil saat
     *                               peristiwa yang sama dikirim ulang.
     * @param  string  $nomorTagihan  Kosong bila tagihan dicari lewat `$idPesananPenyedia`.
     * @param  array<string, mixed>  $muatanMentah
     * @param  string|null  $idPesananPenyedia  Order id sesi gateway (SesiPembayaranLangganan).
     * @param  bool  $kedaluwarsa  Gagal karena sesi bayar habis waktunya, bukan ditolak.
     */
    public function __construct(
        public string $idPeristiwa,
        public string $nomorTagihan,
        public float $jumlah,
        public StatusPembayaranLangganan $status,
        public ?string $referensiEksternal = null,
        public ?string $metode = null,
        public array $muatanMentah = [],
        public ?string $idPesananPenyedia = null,
        public bool $kedaluwarsa = false,
    ) {}

    /**
     * Kabar "masih menunggu" dari gateway tidak menjadi baris pembayaran: sesinya
     * memang sudah berstatus menunggu, dan mencatatnya hanya memicu peristiwa gagal.
     */
    public function hanyaPemberitahuan(): bool
    {
        return $this->status === StatusPembayaranLangganan::Menunggu && $this->idPesananPenyedia !== null;
    }
}

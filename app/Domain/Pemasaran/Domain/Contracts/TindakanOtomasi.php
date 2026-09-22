<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;

/** Satu aksi yang boleh dijalankan otomasi (MARKETING.md 17). */
interface TindakanOtomasi
{
    public function kode(): string;

    public function label(): string;

    /**
     * Aturan validasi konfigurasi aksi ini, dinilai saat versi otomasi disimpan.
     *
     * @return array<string, mixed>
     */
    public function aturan(): array;

    /**
     * Menjalankan aksi; kembaliannya adalah ringkasan satu baris untuk log eksekusi.
     *
     * @param  array<string, mixed>  $konfigurasi
     */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string;
}

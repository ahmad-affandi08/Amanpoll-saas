<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Membuat seluruh aset hasil impor dalam satu transaksi (PRD 8.4).
 *
 * Setiap aset dibuat lewat BuatAset yang sama dengan formulir, jadi riwayat
 * lokasi, kode QR, kode otomatis, dan batas paket berlaku seperti biasa.
 * Satu kegagalan membatalkan seluruhnya; tidak pernah ada impor setengah jadi.
 */
final class ImporAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly BuatAset $buatAset,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $daftarData  isian per aset yang sudah diperiksa PemeriksaImporAset
     * @return list<string> Id aset yang dibuat, urut seperti baris berkas
     */
    public function jalankan(array $daftarData, string $namaBerkas, string $dibuatOleh): array
    {
        return $this->transaksi->jalankan(function () use ($daftarData, $namaBerkas, $dibuatOleh): array {
            // Baris berkode sendiri dibuat lebih dulu. Mesin kode otomatis
            // melewati kode yang sudah tersimpan, tetapi tidak tahu kode yang
            // baru akan disimpan baris sesudahnya: tanpa urutan ini baris kosong
            // di atas bisa mendapat AST-0005 lalu baris "AST-0005" di bawahnya
            // menabrak indeks unik.
            $urutan = array_keys($daftarData);
            usort($urutan, static fn (int $a, int $b): int => [blank($daftarData[$a]['KodeAset'] ?? null), $a] <=> [blank($daftarData[$b]['KodeAset'] ?? null), $b]);

            $idPerBaris = [];

            foreach ($urutan as $indeks) {
                $idPerBaris[$indeks] = $this->buatAset->jalankan($daftarData[$indeks], $dibuatOleh)->Id;
            }

            ksort($idPerBaris);
            $asetId = array_values($idPerBaris);

            $this->audit->catat('Aset.Diimpor', 'Aset', null, dataSesudah: [
                'NamaBerkas' => $namaBerkas,
                'Jumlah' => count($asetId),
                'AsetId' => $asetId,
            ]);

            return $asetId;
        });
    }
}

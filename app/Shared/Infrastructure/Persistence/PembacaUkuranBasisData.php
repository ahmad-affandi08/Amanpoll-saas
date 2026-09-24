<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;

/**
 * Ukuran tiap tabel basis data aplikasi, dari `information_schema.TABLES` (FASE 45).
 *
 * Tidak butuh hak khusus: pengguna MySQL/MariaDB di shared hosting selalu dapat
 * membaca baris information_schema untuk skemanya sendiri. Angkanya perkiraan
 * mesin penyimpanan (InnoDB memperbaruinya berkala), cukup untuk pemantau
 * kapasitas, bukan untuk akuntansi byte per byte.
 *
 * Dipisah dari perintahnya supaya test dapat memberikan ukuran palsu.
 */
class PembacaUkuranBasisData
{
    /**
     * Tabel dasar skema aktif, terbesar lebih dulu.
     *
     * @return list<array{Nama: string, Byte: int, PerkiraanBaris: int}>
     */
    public function ukuranTabel(): array
    {
        return array_values(DB::table('information_schema.TABLES')
            ->selectRaw('TABLE_NAME AS Nama, COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0) AS Byte, COALESCE(TABLE_ROWS, 0) AS PerkiraanBaris')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->orderByDesc('Byte')
            ->get()
            ->map(fn (object $baris): array => [
                'Nama' => (string) $baris->Nama,
                'Byte' => (int) $baris->Byte,
                'PerkiraanBaris' => (int) $baris->PerkiraanBaris,
            ])
            ->all());
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain;

/**
 * Katalog kode JenisPeristiwa yang dikenal platform -- modul baru
 * mendaftarkan peristiwanya sendiri di sini saat mulai mengirim notifikasi,
 * konsisten dengan pola DefinisiKonfigurasi (App\Core\Konfigurasi).
 */
final class KatalogPeristiwaNotifikasi
{
    /**
     * @return array<string, string>
     */
    public static function daftar(): array
    {
        return [
            'Persetujuan.PerluTindakan' => 'Ada permintaan persetujuan yang perlu tindakan Anda',
            'Persetujuan.Disetujui' => 'Permintaan persetujuan Anda disetujui',
            'Persetujuan.Ditolak' => 'Permintaan persetujuan Anda ditolak',
            'Stok.MinimumTercapai' => 'Stok suku cadang mencapai atau di bawah batas minimum',
        ];
    }

    /**
     * @return list<string>
     */
    public static function kodeDikenal(): array
    {
        return array_keys(self::daftar());
    }

    public static function label(string $kode): ?string
    {
        return self::daftar()[$kode] ?? null;
    }
}

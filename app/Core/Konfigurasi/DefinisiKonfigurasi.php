<?php

declare(strict_types=1);

namespace App\Core\Konfigurasi;

/** Katalog kunci konfigurasi yang dikenal platform (namespace per fitur). */
final class DefinisiKonfigurasi
{
    /**
     * @return array<string, array{Namespace: string, Tipe: string, Default: mixed, Label: string, Rahasia: bool}>
     */
    public static function daftar(): array
    {
        return [
            'Notifikasi.EmailAktif' => [
                'Namespace' => 'Notifikasi',
                'Tipe' => 'boolean',
                'Default' => true,
                'Label' => 'Aktifkan notifikasi email',
                'Rahasia' => false,
            ],
            'Notifikasi.PengingatHariSebelum' => [
                'Namespace' => 'Notifikasi',
                'Tipe' => 'integer',
                'Default' => 3,
                'Label' => 'Jumlah hari pengingat sebelum jatuh tempo',
                'Rahasia' => false,
            ],
            'Pemeliharaan.PenomoranOtomatis' => [
                'Namespace' => 'Pemeliharaan',
                'Tipe' => 'boolean',
                'Default' => true,
                'Label' => 'Penomoran perintah kerja otomatis',
                'Rahasia' => false,
            ],
            'Persetujuan.AmbangNilai' => [
                'Namespace' => 'Persetujuan',
                'Tipe' => 'integer',
                'Default' => 0,
                'Label' => 'Ambang nilai transaksi wajib persetujuan (Rupiah)',
                'Rahasia' => false,
            ],
            'Kontrak.HariPeringatan' => [
                'Namespace' => 'Kontrak',
                'Tipe' => 'string',
                'Default' => '90,60,30',
                'Label' => 'Ambang hari peringatan kontrak akan berakhir (dipisah koma)',
                'Rahasia' => false,
            ],
            'Integrasi.WebhookRahasia' => [
                'Namespace' => 'Integrasi',
                'Tipe' => 'string',
                'Default' => null,
                'Label' => 'Kunci rahasia verifikasi webhook keluar',
                'Rahasia' => true,
            ],
        ];
    }

    public static function ada(string $kunci): bool
    {
        return array_key_exists($kunci, self::daftar());
    }

    /**
     * @return array{Namespace: string, Tipe: string, Default: mixed, Label: string, Rahasia: bool}|null
     */
    public static function cari(string $kunci): ?array
    {
        return self::daftar()[$kunci] ?? null;
    }
}

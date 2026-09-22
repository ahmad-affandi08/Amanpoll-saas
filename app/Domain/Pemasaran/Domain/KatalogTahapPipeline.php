<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/**
 * Tahap pipeline bawaan (MARKETING.md 5.3).
 *
 * Tahapnya hidup sebagai baris supaya dapat diubah tanpa rilis, tetapi kode di
 * sini tetap dibutuhkan: corong dan otomasi menyebut tahap tertentu, dan tanpa
 * kode yang stabil keduanya akan putus begitu seseorang mengganti nama tahap.
 */
final class KatalogTahapPipeline
{
    public const BARU = 'BARU';

    public const DIHUBUNGI = 'DIHUBUNGI';

    public const TERLIBAT = 'TERLIBAT';

    public const DEMO = 'DEMO';

    public const TRIAL = 'TRIAL';

    public const AKTIF = 'AKTIF';

    public const QUALIFIED = 'QUALIFIED';

    public const MENANG = 'MENANG';

    public const TIDAK_COCOK = 'TIDAK_COCOK';

    public const HILANG = 'HILANG';

    public const UNSUBSCRIBE = 'UNSUBSCRIBE';

    /**
     * @return list<array{Kode: string, Nama: string, Urutan: int, TahapAkhir: bool, DianggapMenang: bool}>
     */
    public static function bawaan(): array
    {
        return [
            ['Kode' => self::BARU, 'Nama' => 'Baru', 'Urutan' => 10, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::DIHUBUNGI, 'Nama' => 'Dihubungi', 'Urutan' => 20, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::TERLIBAT, 'Nama' => 'Terlibat', 'Urutan' => 30, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::DEMO, 'Nama' => 'Demo', 'Urutan' => 40, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::TRIAL, 'Nama' => 'Trial', 'Urutan' => 50, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::AKTIF, 'Nama' => 'Aktif', 'Urutan' => 60, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::QUALIFIED, 'Nama' => 'Qualified', 'Urutan' => 70, 'TahapAkhir' => false, 'DianggapMenang' => false],
            ['Kode' => self::MENANG, 'Nama' => 'Menang', 'Urutan' => 80, 'TahapAkhir' => true, 'DianggapMenang' => true],
            ['Kode' => self::TIDAK_COCOK, 'Nama' => 'Tidak Cocok', 'Urutan' => 90, 'TahapAkhir' => true, 'DianggapMenang' => false],
            ['Kode' => self::HILANG, 'Nama' => 'Hilang', 'Urutan' => 91, 'TahapAkhir' => true, 'DianggapMenang' => false],
            ['Kode' => self::UNSUBSCRIBE, 'Nama' => 'Unsubscribe', 'Urutan' => 92, 'TahapAkhir' => true, 'DianggapMenang' => false],
        ];
    }
}

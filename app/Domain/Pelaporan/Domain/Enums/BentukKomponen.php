<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Enums;

use App\Domain\Pelaporan\Domain\ValueObjects\DefinisiKpi;

/**
 * Bentuk tampilan satu komponen dasbor (21.04).
 *
 * Pilihan bentuk dibatasi oleh apa yang benar-benar dimiliki KPI: KPI tanpa
 * rincian tidak boleh dipasang sebagai grafik, karena bagan tanpa data hanya
 * akan menjadi kotak kosong yang menyamar sebagai informasi (21.02: no fake
 * chart).
 */
enum BentukKomponen: string
{
    /** Kartu angka tunggal. */
    case Angka = 'Angka';

    /** Batang horizontal untuk perbandingan antar kategori. */
    case Batang = 'Batang';

    /** Garis untuk tren antarwaktu. */
    case Garis = 'Garis';

    /** Donat untuk bagian-dari-keseluruhan, maksimal enam irisan. */
    case Donat = 'Donat';

    /** Tabel rincian; selalu tersedia sebagai padanan bagan yang dapat dibaca. */
    case Tabel = 'Tabel';

    public function butuhRincian(): bool
    {
        return $this !== self::Angka;
    }

    public function label(): string
    {
        return match ($this) {
            self::Angka => 'Kartu angka',
            self::Batang => 'Batang',
            self::Garis => 'Garis tren',
            self::Donat => 'Donat',
            self::Tabel => 'Tabel',
        };
    }

    /**
     * Bentuk yang masuk akal untuk sebuah KPI. Dipakai server untuk validasi
     * dan dikirim ke klien supaya pemilih komponen tidak menawarkan bentuk yang
     * akan ditolak.
     *
     * @return list<self>
     */
    public static function untukKpi(DefinisiKpi $definisi): array
    {
        $bentuk = [self::Angka];

        // KPI yang rinciannya berupa deret waktu cocok sebagai garis; sisanya
        // sebagai perbandingan kategori.
        if (in_array($definisi->kunci, self::KUNCI_DERET_WAKTU, true)) {
            $bentuk[] = self::Garis;
            $bentuk[] = self::Tabel;

            return $bentuk;
        }

        if (in_array($definisi->kunci, self::KUNCI_TANPA_RINCIAN, true)) {
            return $bentuk;
        }

        $bentuk[] = self::Batang;
        $bentuk[] = self::Donat;
        $bentuk[] = self::Tabel;

        return $bentuk;
    }

    /** KPI yang rinciannya adalah deret per hari atau per bulan. */
    private const KUNCI_DERET_WAKTU = [
        'keluhan.masuk',
        'perintah_kerja.selesai',
        'biaya.pemeliharaan',
        'downtime.total_jam',
    ];

    /** KPI yang memang hanya berupa satu angka, tanpa rincian penyusun. */
    private const KUNCI_TANPA_RINCIAN = [
        'keluhan.waktu_respons',
        'sla.berisiko',
        'keandalan.mttr',
        'keandalan.mtbf',
        'biaya.per_aset',
        'kepatuhan.akan_kedaluwarsa',
    ];
}

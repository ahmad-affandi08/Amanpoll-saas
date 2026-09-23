<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Domain\ValueObjects;

/**
 * Tata letak kolom berkas ASPAK.
 *
 * ASPAK mengubah templat unggahnya dari waktu ke waktu, dan judul kolomnya
 * tidak sama di setiap versi. Karena itu tata letaknya disimpan sebagai data
 * per organisasi (kunci konfigurasi `Aspak.ProfilKolom`), bukan ditanam di
 * kode: begitu rumah sakit memegang templat resminya, judul kolom diperbaiki
 * lewat pengaturan tanpa perlu rilis baru.
 *
 * Nilai bawaan di bawah adalah titik mulai yang wajar, BUKAN salinan resmi
 * templat ASPAK. Cocokkan dengan templat yang berlaku sebelum dipakai
 * mengunggah sungguhan.
 */
final class ProfilKolomAspak
{
    /**
     * Ruas yang dapat dipetakan ke kolom berkas, beserta keterangannya.
     *
     * @var array<string, string>
     */
    public const RUAS = [
        'KodeAlkes' => 'Kode alkes ASPAK hasil pemetaan',
        'NamaAlkes' => 'Nama alkes menurut katalog ASPAK',
        'KodeRuang' => 'Kode ruang ASPAK pada lokasi aset',
        'NamaRuang' => 'Nama lokasi aset di Amanpoll',
        'KodeAset' => 'Kode aset internal',
        'NamaAset' => 'Nama aset internal',
        'Merek' => 'Merek dari model aset',
        'Tipe' => 'Nama model aset',
        'NomorSeri' => 'Nomor seri',
        'TahunPerolehan' => 'Tahun dari tanggal perolehan',
        'Kondisi' => 'Kondisi menurut istilah ASPAK',
        'Jumlah' => 'Selalu 1; ASPAK mencatat per unit',
        'HargaPerolehan' => 'Harga perolehan',
        'SumberDana' => 'Sumber dana perolehan',
        'TanggalKalibrasiTerakhir' => 'Tanggal pelaksanaan kalibrasi terakhir',
        'BerlakuKalibrasiSampai' => 'Masa berlaku kalibrasi terakhir',
    ];

    /**
     * @param  list<array{ruas: string, judul: string}>  $kolom
     */
    private function __construct(public readonly array $kolom) {}

    public static function bawaan(): self
    {
        $kolom = [];

        foreach (self::susunanBawaan() as $ruas => $judul) {
            $kolom[] = ['ruas' => $ruas, 'judul' => $judul];
        }

        return new self($kolom);
    }

    /**
     * Membaca profil tersimpan; yang tidak dikenali diabaikan supaya satu
     * entri rusak tidak membuat seluruh ekspor gagal.
     */
    public static function dariKonfigurasi(?string $json): self
    {
        if ($json === null || trim($json) === '') {
            return self::bawaan();
        }

        $terurai = json_decode($json, true);

        if (! is_array($terurai)) {
            return self::bawaan();
        }

        $kolom = [];

        foreach ($terurai as $satu) {
            if (! is_array($satu)) {
                continue;
            }

            $ruas = $satu['ruas'] ?? null;
            $judul = $satu['judul'] ?? null;

            if (! is_string($ruas) || ! isset(self::RUAS[$ruas])) {
                continue;
            }

            $kolom[] = ['ruas' => $ruas, 'judul' => is_string($judul) && $judul !== '' ? $judul : $ruas];
        }

        return $kolom === [] ? self::bawaan() : new self($kolom);
    }

    /** @return list<string> */
    public function judul(): array
    {
        return array_map(static fn (array $satu): string => $satu['judul'], $this->kolom);
    }

    /** @return list<string> */
    public function ruas(): array
    {
        return array_map(static fn (array $satu): string => $satu['ruas'], $this->kolom);
    }

    /** @return array<string, string> */
    private static function susunanBawaan(): array
    {
        return [
            'KodeRuang' => 'Kode Ruang',
            'NamaRuang' => 'Nama Ruang',
            'KodeAlkes' => 'Kode Alat',
            'NamaAlkes' => 'Nama Alat',
            'Merek' => 'Merk',
            'Tipe' => 'Tipe',
            'NomorSeri' => 'Nomor Seri',
            'TahunPerolehan' => 'Tahun Pengadaan',
            'Jumlah' => 'Jumlah',
            'Kondisi' => 'Kondisi',
            'HargaPerolehan' => 'Harga',
            'SumberDana' => 'Sumber Anggaran',
            'TanggalKalibrasiTerakhir' => 'Tanggal Kalibrasi',
            'BerlakuKalibrasiSampai' => 'Kalibrasi Berlaku Sampai',
        ];
    }
}

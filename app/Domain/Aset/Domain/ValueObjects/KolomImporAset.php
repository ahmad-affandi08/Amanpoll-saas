<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\ValueObjects;

/**
 * Kolom berkas impor aset (PRD 8.4 "Impor Aset").
 *
 * Satu daftar untuk templat, lembar petunjuk, pemetaan kepala berkas, dan
 * label galat, supaya judul kolom yang diunduh pengguna tidak pernah berbeda
 * dengan yang dikenali saat berkasnya diunggah kembali.
 *
 * Kolomnya mengikuti isian `SimpanAsetRequest`. Rujukan diisi kode, bukan Id;
 * `rujukan` menyebut tabel yang dicari. `Versi` tidak ada karena itu penanda
 * penguncian optimistis, bukan data aset. Merek tidak punya kolom sendiri:
 * aset tidak menyimpan merek dan merek tidak punya kode -- merek mengikuti
 * model yang dipilih.
 */
final class KolomImporAset
{
    public const RUJUKAN_KATEGORI = 'kategori';

    public const RUJUKAN_MODEL = 'model';

    public const RUJUKAN_ASPAK = 'aspak';

    public const RUJUKAN_PENYEDIA = 'penyedia';

    public const RUJUKAN_UNIT = 'unit';

    public const RUJUKAN_UNIT_PENGELOLA = 'unitPengelola';

    public const RUJUKAN_LOKASI = 'lokasi';

    /** Kolom yang harus ada di kepala berkas; tanpanya tidak satu baris pun bisa sah. */
    public const RUAS_WAJIB = ['Nama', 'KategoriAsetId'];

    /** Isian formulir yang punya nilai bawaan di formulir aset bila dikosongkan. */
    public const BAWAAN = ['Status' => 'Aktif', 'Kondisi' => 'Baik', 'TingkatKritis' => 'Normal'];

    /**
     * @return list<array{judul: string, ruas: string, rujukan: string|null, keterangan: string}>
     */
    public static function daftar(): array
    {
        return [
            ['judul' => 'Kode Aset', 'ruas' => 'KodeAset', 'rujukan' => null, 'keterangan' => 'Kosongkan supaya diisi otomatis. Tidak boleh sama dengan kode aset yang sudah ada atau baris lain.'],
            ['judul' => 'Nama', 'ruas' => 'Nama', 'rujukan' => null, 'keterangan' => 'Wajib. Nama aset, paling banyak 200 karakter.'],
            ['judul' => 'Kode Kategori', 'ruas' => 'KategoriAsetId', 'rujukan' => self::RUJUKAN_KATEGORI, 'keterangan' => 'Wajib. Kode kategori aset, lihat daftar di bawah.'],
            ['judul' => 'Kode Model', 'ruas' => 'ModelAsetId', 'rujukan' => self::RUJUKAN_MODEL, 'keterangan' => 'Kode model aset. Merek ikut model yang dipilih.'],
            ['judul' => 'Kode ASPAK', 'ruas' => 'AlkesAspakId', 'rujukan' => self::RUJUKAN_ASPAK, 'keterangan' => 'Kode nomenklatur alkes ASPAK yang sudah diimpor ke katalog.'],
            ['judul' => 'Kode Penyedia', 'ruas' => 'PenyediaId', 'rujukan' => self::RUJUKAN_PENYEDIA, 'keterangan' => 'Kode penyedia (vendor) aset.'],
            ['judul' => 'Kode Unit Organisasi', 'ruas' => 'UnitOrganisasiId', 'rujukan' => self::RUJUKAN_UNIT, 'keterangan' => 'Kode unit pemilik atau pemakai aset, mis. ICU.'],
            ['judul' => 'Kode Unit Pengelola', 'ruas' => 'UnitPengelolaId', 'rujukan' => self::RUJUKAN_UNIT_PENGELOLA, 'keterangan' => 'Kode bagian yang memelihara aset, mis. IPSRS atau IT. Kosongkan bila tanpa unit pengelola.'],
            ['judul' => 'Kode Lokasi', 'ruas' => 'LokasiId', 'rujukan' => self::RUJUKAN_LOKASI, 'keterangan' => 'Kode lokasi awal aset.'],
            ['judul' => 'Nomor Seri', 'ruas' => 'NomorSeri', 'rujukan' => null, 'keterangan' => 'Paling banyak 160 karakter.'],
            ['judul' => 'Nomor Inventaris', 'ruas' => 'NomorInventaris', 'rujukan' => null, 'keterangan' => 'Paling banyak 160 karakter.'],
            ['judul' => 'Nomor Registrasi Eksternal', 'ruas' => 'NomorRegistrasiEksternal', 'rujukan' => null, 'keterangan' => 'Mis. nomor registrasi ASPAK atau SIMAK BMN.'],
            ['judul' => 'Tanggal Perolehan', 'ruas' => 'TanggalPerolehan', 'rujukan' => null, 'keterangan' => 'Format 2024-05-31 atau 31/05/2024.'],
            ['judul' => 'Tanggal Mulai Operasi', 'ruas' => 'TanggalMulaiOperasi', 'rujukan' => null, 'keterangan' => 'Format 2024-05-31 atau 31/05/2024.'],
            ['judul' => 'Tanggal Akhir Operasi', 'ruas' => 'TanggalAkhirOperasi', 'rujukan' => null, 'keterangan' => 'Tidak boleh sebelum tanggal mulai operasi.'],
            ['judul' => 'Harga Perolehan', 'ruas' => 'HargaPerolehan', 'rujukan' => null, 'keterangan' => 'Angka tanpa titik ribuan, mis. 15000000 atau 15000000.50.'],
            ['judul' => 'Nilai Residu', 'ruas' => 'NilaiResidu', 'rujukan' => null, 'keterangan' => 'Angka tanpa titik ribuan. Kosongkan supaya dihitung dari kategori.'],
            ['judul' => 'Mata Uang', 'ruas' => 'MataUang', 'rujukan' => null, 'keterangan' => 'Tiga huruf, mis. IDR. Kosong berarti IDR.'],
            ['judul' => 'Sumber Dana', 'ruas' => 'SumberDana', 'rujukan' => null, 'keterangan' => 'Mis. APBD, BLUD, Hibah.'],
            ['judul' => 'Metode Penyusutan', 'ruas' => 'MetodePenyusutan', 'rujukan' => null, 'keterangan' => 'Kosongkan supaya mengikuti kategori.'],
            ['judul' => 'Umur Manfaat (Bulan)', 'ruas' => 'UmurManfaatBulan', 'rujukan' => null, 'keterangan' => 'Bilangan bulat. Kosongkan supaya mengikuti kategori.'],
            ['judul' => 'Status', 'ruas' => 'Status', 'rujukan' => null, 'keterangan' => 'Aktif, Nonaktif, Dipinjam, Rusak, atau Diarsipkan. Kosong berarti Aktif.'],
            ['judul' => 'Kondisi', 'ruas' => 'Kondisi', 'rujukan' => null, 'keterangan' => 'Baik, Perlu Perhatian, atau Rusak. Kosong berarti Baik.'],
            ['judul' => 'Tingkat Kritis', 'ruas' => 'TingkatKritis', 'rujukan' => null, 'keterangan' => 'Normal, Tinggi, atau Sangat Tinggi. Kosong berarti Normal.'],
            ['judul' => 'Kode Batang', 'ruas' => 'KodeBatang', 'rujukan' => null, 'keterangan' => 'Isi barcode lama yang sudah tertempel, bila ada.'],
            ['judul' => 'NFC UID', 'ruas' => 'NfcUid', 'rujukan' => null, 'keterangan' => 'UID tag NFC yang sudah tertempel, bila ada.'],
            ['judul' => 'Catatan', 'ruas' => 'Catatan', 'rujukan' => null, 'keterangan' => 'Teks bebas.'],
        ];
    }

    /**
     * Kolom yang dimuat di templat organisasi ini.
     *
     * Kolom unit pengelola hanya ikut bila organisasi memakai fitur itu, sama
     * seperti isian formulir dan kolom ekspor aset: organisasi dengan satu
     * bagian pemeliharaan tidak melihat perubahan apa pun. Saat membaca,
     * kolomnya tetap dikenali di organisasi mana pun.
     *
     * @return list<array{judul: string, ruas: string, rujukan: string|null, keterangan: string}>
     */
    public static function untukTemplat(bool $unitPengelolaDipakai): array
    {
        return array_values(array_filter(
            self::daftar(),
            static fn (array $kolom): bool => $unitPengelolaDipakai || $kolom['ruas'] !== 'UnitPengelolaId',
        ));
    }

    /**
     * Peta judul ternormal ke ruas: huruf kecil, tanpa spasi dan tanda baca,
     * jadi "Kode Kategori", "kode kategori", dan "KodeKategori" sama-sama dikenali.
     *
     * @return array<string, string>
     */
    public static function petaKepala(): array
    {
        $peta = [];

        foreach (self::daftar() as $kolom) {
            $peta[self::normalkan($kolom['judul'])] = $kolom['ruas'];
        }

        return $peta;
    }

    /** @return array<string, string> ruas => judul kolom */
    public static function judulPerRuas(): array
    {
        $hasil = [];

        foreach (self::daftar() as $kolom) {
            $hasil[$kolom['ruas']] = $kolom['judul'];
        }

        return $hasil;
    }

    public static function normalkan(string $judul): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', mb_strtolower($judul));
    }
}

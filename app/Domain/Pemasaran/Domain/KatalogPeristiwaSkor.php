<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain;

/**
 * Sinyal yang boleh diberi bobot skor (MARKETING.md 5.4).
 *
 * Sengaja tidak sama dengan KatalogPeristiwaPemasaran. Taxonomy bagian 23
 * mendaftar apa yang boleh masuk `EventPemasaran`, sedangkan yang memengaruhi
 * skor lebih luas dari itu: dua di antaranya dihitung dari keadaan prospek dan
 * tidak pernah menjadi baris peristiwa sama sekali.
 *
 * Tiap sinyal membawa asalnya, dan itu yang membuat daftar ini berguna. Bobot
 * untuk sinyal yang belum ada penghasilnya — `EmailBounce`, sampai domain email
 * lahir — tidak akan pernah terpakai. Dulu keadaan itu tidak terlihat sama
 * sekali: bobotnya tersimpan rapi di konfigurasi dan diam-diam tidak pernah
 * menyumbang apa pun. Sekarang ia dinyatakan `Tertunda` dan tampil apa adanya
 * di konsol.
 */
final class KatalogPeristiwaSkor
{
    /** Datang dari baris EventPemasaran. */
    public const ASAL_PERISTIWA = 'Peristiwa';

    /** Dihitung dari keadaan prospek, bukan dari baris peristiwa. */
    public const ASAL_TURUNAN = 'Turunan';

    /** Sudah dikenal, tetapi belum ada yang menghasilkannya. */
    public const ASAL_TERTUNDA = 'Tertunda';

    public const AKTIF_TIGA_HARI = 'AktifTigaHari';

    public const TIDAK_AKTIF_EMPAT_BELAS_HARI = 'TidakAktifEmpatBelasHari';

    public const EMAIL_BOUNCE = 'EmailBounce';

    /**
     * Seluruh sinyal beserta asalnya.
     *
     * @return array<string, string> kode => asal
     */
    public static function semua(): array
    {
        $hasil = [];

        foreach (KatalogPeristiwaPemasaran::semua() as $kode) {
            $hasil[$kode] = self::ASAL_PERISTIWA;
        }

        foreach (self::turunan() as $kode) {
            $hasil[$kode] = self::ASAL_TURUNAN;
        }

        $hasil[self::EMAIL_BOUNCE] = self::ASAL_TERTUNDA;

        return $hasil;
    }

    /** @return list<string> */
    public static function kode(): array
    {
        return array_keys(self::semua());
    }

    /** @return list<string> */
    public static function turunan(): array
    {
        return [self::AKTIF_TIGA_HARI, self::TIDAK_AKTIF_EMPAT_BELAS_HARI];
    }

    public static function dikenal(string $kode): bool
    {
        return array_key_exists($kode, self::semua());
    }

    public static function asal(string $kode): ?string
    {
        return self::semua()[$kode] ?? null;
    }

    /**
     * Bobot bawaan, persis contoh MARKETING.md 5.4. Dipakai sekali saat tabel
     * aturannya disemai; sesudah itu yang berlaku adalah isi tabelnya.
     *
     * @return array<string, int>
     */
    public static function bobotBawaan(): array
    {
        return [
            KatalogPeristiwaPemasaran::HARGA_DILIHAT => 5,
            KatalogPeristiwaPemasaran::DEMO_DIMULAI => 8,
            KatalogPeristiwaPemasaran::FORMULIR_DIKIRIM => 10,
            KatalogPeristiwaPemasaran::TRIAL_DIMULAI => 15,
            KatalogPeristiwaPemasaran::ASET_PERTAMA_DIBUAT => 20,
            KatalogPeristiwaPemasaran::PENGGUNA_PERTAMA_DIUNDANG => 15,
            KatalogPeristiwaPemasaran::PERINTAH_KERJA_PERTAMA_DIBUAT => 15,
            self::AKTIF_TIGA_HARI => 10,
            self::EMAIL_BOUNCE => -10,
            self::TIDAK_AKTIF_EMPAT_BELAS_HARI => -20,
        ];
    }
}

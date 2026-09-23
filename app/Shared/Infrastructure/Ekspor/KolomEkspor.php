<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Satu kolom pada berkas ekspor daftar.
 *
 * Judul dan cara mengambil nilainya disebut bersama supaya kepala kolom tidak
 * pernah berselisih dengan isinya -- kesalahan yang baru ketahuan setelah
 * berkasnya sampai ke tangan orang lain.
 *
 * Kelasnya sengaja tidak generic. Satu daftar kolom biasanya mencampur
 * `atribut()` yang berlaku bagi Model apa pun dengan `dari()` yang menyebut
 * modelnya secara tepat; mengikat keduanya pada satu parameter tipe membuat
 * PHPStan menyatukannya menjadi Model dan kemudian menolak kuerinya sendiri.
 * Tipe modelnya tetap diperiksa di tempat yang penting, yaitu closure
 * `dari()`.
 */
final class KolomEkspor
{
    /**
     * @param  string|null  $atribut  Nama atribut yang dibaca, bila kolom ini memang membaca satu atribut.
     */
    private function __construct(
        public readonly string $judul,
        private readonly Closure $ambil,
        private readonly ?string $atribut = null,
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  callable(TModel): (string|float|int|null)  $ambil
     */
    public static function dari(string $judul, callable $ambil): self
    {
        return new self($judul, $ambil(...));
    }

    /** Kolom yang nilainya cukup dibaca apa adanya dari atribut model. */
    public static function atribut(string $judul, string $atribut): self
    {
        return new self($judul, static function (Model $baris) use ($atribut): string {
            $nilai = $baris->getAttribute($atribut);

            return $nilai === null ? '' : (string) $nilai;
        }, $atribut);
    }

    /**
     * Kolom tanggal, diseragamkan supaya dapat diurutkan di Excel.
     *
     * Waktu berjam disimpan UTC; pembaca berkas membacanya sebagai jam dinding
     * rumah sakitnya, jadi dicetak di zona organisasi. Kolom `date` tidak
     * digeser: nilainya tanggal kalender, bukan momen, dan menggesernya ke zona
     * bertanda negatif akan memundurkannya satu hari.
     */
    public static function tanggal(string $judul, string $atribut, string $format = 'Y-m-d'): self
    {
        return new self($judul, static function (Model $baris, string $zona) use ($atribut, $format): string {
            $nilai = $baris->getAttribute($atribut);
            if (! $nilai instanceof DateTimeInterface) {
                return '';
            }

            return self::berjam($baris, $atribut)
                ? CarbonImmutable::instance($nilai)->setTimezone($zona)->format($format)
                : $nilai->format($format);
        }, $atribut);
    }

    /** @param  string  $zona  Zona organisasi pemilik berkas, untuk kolom waktu berjam. */
    public function nilai(Model $baris, string $zona = 'UTC'): string|float|int|null
    {
        $this->tolakAtributTersembunyi($baris);

        return ($this->ambil)($baris, $zona);
    }

    /** Atribut tanpa cast `date`/`immutable_date` diperlakukan sebagai momen. */
    private static function berjam(Model $baris, string $atribut): bool
    {
        $cast = strtolower(explode(':', (string) ($baris->getCasts()[$atribut] ?? 'datetime'), 2)[0]);

        return ! in_array($cast, ['date', 'immutable_date'], true);
    }

    /**
     * Menolak kolom yang menyebut atribut ber-`$hidden`.
     *
     * `$hidden` hanya menahan serialisasi, dan ekspor tidak lewat sana:
     * `getAttribute()` membaca nilainya tanpa pernah menyentuh daftar itu --
     * diperiksa di HasAttributes pada framework yang terpasang. Jadi model yang
     * menyembunyikan HashKunci tetap menyerahkannya bulat-bulat ke berkas bila
     * ada yang menuliskan kolomnya.
     *
     * Selama ini yang menahan hanya ingatan penulis kolomnya. Penjaga ini
     * mengubah kebocoran senyap menjadi kegagalan keras pada baris pertama,
     * karena berkas ekspor beredar di luar aplikasi dan tidak dapat ditarik
     * kembali.
     */
    private function tolakAtributTersembunyi(Model $baris): void
    {
        if ($this->atribut === null || ! in_array($this->atribut, $baris->getHidden(), true)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Kolom ekspor "%s" menyebut atribut %s yang ditandai $hidden pada %s. '
            .'Atribut tersembunyi tidak boleh keluar lewat berkas ekspor.',
            $this->judul,
            $this->atribut,
            $baris::class,
        ));
    }
}

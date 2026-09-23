<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use Closure;
use Illuminate\Database\Eloquent\Model;

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
    private function __construct(
        public readonly string $judul,
        private readonly Closure $ambil,
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
        });
    }

    /** Kolom tanggal, diseragamkan supaya dapat diurutkan di Excel. */
    public static function tanggal(string $judul, string $atribut, string $format = 'Y-m-d'): self
    {
        return new self($judul, static function (Model $baris) use ($atribut, $format): string {
            $nilai = $baris->getAttribute($atribut);

            return $nilai instanceof \DateTimeInterface ? $nilai->format($format) : '';
        });
    }

    public function nilai(Model $baris): string|float|int|null
    {
        return ($this->ambil)($baris);
    }
}

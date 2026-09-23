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
 * Kontravarian pada modelnya: kolom yang sanggup membaca Model apa pun boleh
 * dipakai di daftar kolom untuk model yang lebih khusus. Itu yang membuat
 * `atribut()` yang umum dapat berdampingan dengan `dari()` yang menyebut
 * modelnya secara tepat.
 *
 * @template-contravariant TModel of Model
 */
final class KolomEkspor
{
    /** @param  Closure(TModel): (string|float|int|null)  $ambil */
    private function __construct(
        public readonly string $judul,
        private readonly Closure $ambil,
    ) {}

    /**
     * @template TBaru of Model
     *
     * @param  callable(TBaru): (string|float|int|null)  $ambil
     * @return self<TBaru>
     */
    public static function dari(string $judul, callable $ambil): self
    {
        return new self($judul, $ambil(...));
    }

    /**
     * Kolom yang nilainya cukup dibaca apa adanya dari atribut model.
     *
     * @return self<Model>
     */
    public static function atribut(string $judul, string $atribut): self
    {
        return new self($judul, static function (Model $baris) use ($atribut): string {
            $nilai = $baris->getAttribute($atribut);

            return $nilai === null ? '' : (string) $nilai;
        });
    }

    /**
     * Kolom tanggal, diseragamkan supaya dapat diurutkan di Excel.
     *
     * @return self<Model>
     */
    public static function tanggal(string $judul, string $atribut, string $format = 'Y-m-d'): self
    {
        return new self($judul, static function (Model $baris) use ($atribut, $format): string {
            $nilai = $baris->getAttribute($atribut);

            return $nilai instanceof \DateTimeInterface ? $nilai->format($format) : '';
        });
    }

    /** @param  TModel  $baris */
    public function nilai(Model $baris): string|float|int|null
    {
        return ($this->ambil)($baris);
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * Membaca relasi belongsTo yang kunci asingnya boleh kosong.
 *
 * Tipe relasi Eloquent tidak menyatakan nullability kunci asingnya, sehingga
 * analisis statis menganggap relasinya selalu terisi dan menilai `?->` tidak
 * perlu. Mengikuti anggapan itu berakibat nyata: aset tanpa lokasi memicu
 * peringatan "property on null" di tengah ekspor, bukan sekadar sel kosong.
 *
 * Diambil lewat tipe Model dasar supaya nullnya tidak hilang di jalan.
 */
final class BacaRelasi
{
    public static function model(Model $induk, string $nama): ?Model
    {
        $terkait = $induk->getRelationValue($nama);

        return $terkait instanceof Model ? $terkait : null;
    }

    public static function teks(?Model $model, string $atribut): string
    {
        if ($model === null) {
            return '';
        }

        $nilai = $model->getAttribute($atribut);

        return is_scalar($nilai) ? (string) $nilai : '';
    }
}

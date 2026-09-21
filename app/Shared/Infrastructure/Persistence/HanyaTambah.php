<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris hanya boleh ditambah, tidak pernah diubah atau dihapus (24).
 *
 * Dipakai catatan audit dan catatan akses. Skema Amanpoll tidak menyediakan
 * rantai hash maupun penyimpanan write-once, jadi ketahanannya ditegakkan di
 * lapisan aplikasi: satu `->update()` atau `->delete()` yang tidak disengaja —
 * lewat pembersihan massal, perintah artisan, atau kode baru — berhenti di sini
 * alih-alih diam-diam menulis ulang riwayat.
 *
 * Dua batas yang perlu diketahui sebelum mengandalkan trait ini:
 *
 * 1. Peristiwa model tidak menyala pada operasi massal, sehingga
 *    `Model::query()->delete()` tetap lolos. Itu memang dipakai kebijakan
 *    retensi `catatan-akses:bersihkan`, yang sah dan terjadwal.
 * 2. Ia tidak menghalangi siapa pun yang memegang akses langsung ke basis
 *    data. Perlindungan pada tingkat itu urusan hak akses MySQL dan cadangan.
 */
trait HanyaTambah
{
    protected static function bootHanyaTambah(): void
    {
        static::updating(function (Model $model): void {
            throw new AturanBisnisDilanggar(
                class_basename($model).' bersifat hanya-tambah dan tidak dapat diubah.',
            );
        });

        static::deleting(function (Model $model): void {
            throw new AturanBisnisDilanggar(
                class_basename($model).' bersifat hanya-tambah dan tidak dapat dihapus.',
            );
        });
    }
}

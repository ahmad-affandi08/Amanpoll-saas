<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\Eloquent\Model;

/** Baris hanya boleh ditambah, tidak pernah diubah atau dihapus (24). */
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

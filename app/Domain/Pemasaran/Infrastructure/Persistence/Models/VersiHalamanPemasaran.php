<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu revisi isi halaman (MARKETING.md 8).
 *
 * Hanya-tambah. Menyunting draf melahirkan versi baru, karena rollback ke versi
 * yang isinya sempat ditulis ulang bukan rollback.
 */
final class VersiHalamanPemasaran extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'VersiHalamanPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'HalamanPemasaranId',
        'Nomor',
        'Judul',
        'MetaJudul',
        'MetaDeskripsi',
        'Kanonik',
        'OgJudul',
        'OgDeskripsi',
        'OgGambar',
        'SkemaTipe',
        'Catatan',
        'DibuatOlehPlatformId',
        'DibuatPada',
    ];

    protected function casts(): array
    {
        return [
            'Nomor' => 'integer',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<HalamanPemasaran, $this> */
    public function halaman(): BelongsTo
    {
        return $this->belongsTo(HalamanPemasaran::class, 'HalamanPemasaranId', 'Id');
    }

    /** @return HasMany<BlokHalamanPemasaran, $this> */
    public function blok(): HasMany
    {
        return $this->hasMany(BlokHalamanPemasaran::class, 'VersiHalamanPemasaranId', 'Id')
            ->orderBy('Urutan');
    }
}

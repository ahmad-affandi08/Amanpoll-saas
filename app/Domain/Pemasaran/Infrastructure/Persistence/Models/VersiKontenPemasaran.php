<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu versi naskah konten; yang sudah terbit tidak pernah disunting di tempat (MARKETING.md 9). */
final class VersiKontenPemasaran extends ModelDasar
{
    protected $table = 'VersiKontenPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'KontenPemasaranId',
        'Nomor',
        'Judul',
        'Ringkasan',
        'IsiMarkdown',
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

    /** @return BelongsTo<KontenPemasaran, $this> */
    public function konten(): BelongsTo
    {
        return $this->belongsTo(KontenPemasaran::class, 'KontenPemasaranId', 'Id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu peristiwa pemasaran (MARKETING.md 23). */
final class EventPemasaran extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'EventPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'PengenalPengunjung',
        'SesiPengunjungId',
        'Jenis',
        'Url',
        'DataTambahan',
        'TerjadiPada',
    ];

    protected function casts(): array
    {
        return [
            'DataTambahan' => 'array',
            'TerjadiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SesiPengunjung, $this> */
    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiPengunjung::class, 'SesiPengunjungId', 'Id');
    }
}

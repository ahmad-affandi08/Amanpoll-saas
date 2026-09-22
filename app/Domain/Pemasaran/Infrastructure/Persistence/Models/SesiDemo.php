<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu kunjungan ke demo, dari mulai sampai selesai (MARKETING.md 11). */
final class SesiDemo extends ModelDasar
{
    protected $table = 'SesiDemo';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'DemoPemasaranId',
        'PengenalPengunjung',
        'SesiPengunjungId',
        'Status',
        'MulaiPada',
        'KedaluwarsaPada',
        'SelesaiPada',
        'AlasanSelesai',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusSesiDemo::class,
            'MulaiPada' => 'immutable_datetime',
            'KedaluwarsaPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<DemoPemasaran, $this> */
    public function demo(): BelongsTo
    {
        return $this->belongsTo(DemoPemasaran::class, 'DemoPemasaranId', 'Id');
    }

    /** @return HasMany<EventDemo, $this> */
    public function event(): HasMany
    {
        return $this->hasMany(EventDemo::class, 'SesiDemoId', 'Id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu konten pada satu channel, dengan caption, media, CTA, dan UTM sendiri (MARKETING.md 18). */
final class DistribusiKontenSosial extends ModelDasar
{
    protected $table = 'DistribusiKontenSosial';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'KontenSosialId',
        'Channel',
        'Caption',
        'MediaUrl',
        'Cta',
        'TautanTujuan',
        'UtmSource',
        'UtmMedium',
        'UtmTerm',
        'UtmContent',
        'Status',
        'IdPostPenyedia',
        'UrlTerbit',
        'Galat',
        'Percobaan',
        'DiprosesPada',
        'TerbitPada',
    ];

    protected function casts(): array
    {
        return [
            'Channel' => ChannelSosial::class,
            'Status' => StatusKontenSosial::class,
            'Percobaan' => 'integer',
            'DiprosesPada' => 'immutable_datetime',
            'TerbitPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<KontenSosial, $this> */
    public function konten(): BelongsTo
    {
        return $this->belongsTo(KontenSosial::class, 'KontenSosialId', 'Id');
    }

    /** @return HasMany<JadwalKontenSosial, $this> */
    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalKontenSosial::class, 'DistribusiKontenSosialId', 'Id');
    }
}

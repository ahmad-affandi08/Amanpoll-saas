<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Satu kunjungan pengunjung anonim (MARKETING.md 24). */
final class SesiPengunjung extends ModelDasar
{
    protected $table = 'SesiPengunjung';

    public $timestamps = false;

    protected $fillable = [
        'PengenalPengunjung',
        'AlamatIp',
        'AgenPengguna',
        'Perangkat',
        'Referrer',
        'LandingUrl',
        'Host',
        'DimulaiPada',
        'TerakhirAktifPada',
    ];

    protected function casts(): array
    {
        return [
            'DimulaiPada' => 'immutable_datetime',
            'TerakhirAktifPada' => 'immutable_datetime',
        ];
    }

    /** @return HasOne<UtmPemasaran, $this> */
    public function utm(): HasOne
    {
        return $this->hasOne(UtmPemasaran::class, 'SesiPengunjungId', 'Id');
    }
}

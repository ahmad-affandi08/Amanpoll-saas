<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

/** First touch dan last touch satu pengunjung (MARKETING.md 14). */
final class AttributionPemasaran extends ModelDasar
{
    protected $table = 'AttributionPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'PengenalPengunjung',
        'SumberPertama',
        'MediumPertama',
        'KampanyePertama',
        'KampanyeIdPertama',
        'LandingPertama',
        'ReferrerPertama',
        'SentuhanPertamaPada',
        'SumberTerakhir',
        'MediumTerakhir',
        'KampanyeTerakhir',
        'KampanyeIdTerakhir',
        'LandingTerakhir',
        'ReferrerTerakhir',
        'SentuhanTerakhirPada',
    ];

    protected function casts(): array
    {
        return [
            'SentuhanPertamaPada' => 'immutable_datetime',
            'SentuhanTerakhirPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function sudahAdaSentuhanPertama(): bool
    {
        return $this->SentuhanPertamaPada !== null;
    }
}

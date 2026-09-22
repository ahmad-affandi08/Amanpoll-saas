<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Satu setelan domain Pemasaran (MARKETING.md 30). */
final class KonfigurasiPemasaran extends ModelDasar
{
    protected $table = 'KonfigurasiPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kunci',
        'Nilai',
        'Keterangan',
    ];

    protected function casts(): array
    {
        return [
            'Nilai' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }
}

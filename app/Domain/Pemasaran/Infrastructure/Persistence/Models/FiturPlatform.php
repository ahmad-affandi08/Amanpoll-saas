<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

/**
 * Status hidup satu modul platform (MARKETING.md 31).
 *
 * Tanpa MilikOrganisasi: flag berlaku untuk seluruh instalasi, bukan per tenant.
 */
final class FiturPlatform extends ModelDasar
{
    protected $table = 'FiturPlatform';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Keterangan',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }
}

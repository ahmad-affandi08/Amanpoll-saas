<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Satu aturan redirect situs publik (MARKETING.md 9). */
final class RedirectPemasaran extends ModelDasar
{
    protected $table = 'RedirectPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Dari',
        'Ke',
        'Kode',
        'Aktif',
        'Catatan',
        'JumlahDipakai',
        'TerakhirDipakaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Kode' => KodeRedirect::class,
            'Aktif' => 'boolean',
            'JumlahDipakai' => 'integer',
            'TerakhirDipakaiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }
}

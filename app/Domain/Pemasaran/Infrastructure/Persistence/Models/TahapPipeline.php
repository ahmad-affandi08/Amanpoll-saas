<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Satu tahap pipeline prospek (MARKETING.md 5.3). */
final class TahapPipeline extends ModelDasar
{
    protected $table = 'TahapPipeline';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Kode', 'Nama', 'Urutan', 'TahapAkhir', 'DianggapMenang'];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'TahapAkhir' => 'boolean',
            'DianggapMenang' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }
}

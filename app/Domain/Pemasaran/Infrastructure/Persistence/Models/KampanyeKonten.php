<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisKontenKampanye;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Materi yang ditautkan ke satu kampanye (MARKETING.md 13). */
final class KampanyeKonten extends ModelDasar
{
    protected $table = 'KampanyeKonten';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'KampanyeId',
        'Jenis',
        'Judul',
        'Tautan',
        'Catatan',
        'Urutan',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisKontenKampanye::class,
            'Urutan' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}

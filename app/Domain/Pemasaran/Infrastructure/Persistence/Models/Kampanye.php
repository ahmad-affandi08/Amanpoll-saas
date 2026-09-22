<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kampanye pemasaran (MARKETING.md 13). */
final class Kampanye extends ModelDasar
{
    protected $table = 'Kampanye';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Objective',
        'Status',
        'MulaiPada',
        'SelesaiPada',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'immutable_date',
            'SelesaiPada' => 'immutable_date',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<KampanyeChannel, $this> */
    public function channel(): HasMany
    {
        return $this->hasMany(KampanyeChannel::class, 'KampanyeId', 'Id');
    }
}

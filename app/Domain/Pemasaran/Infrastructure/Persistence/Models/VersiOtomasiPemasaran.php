<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusOtomasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu versi definisi otomasi (MARKETING.md 17). */
final class VersiOtomasiPemasaran extends ModelDasar
{
    protected $table = 'VersiOtomasiPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OtomasiPemasaranId',
        'Nomor',
        'Status',
        'DiterbitkanOlehId',
        'DiterbitkanPada',
    ];

    protected function casts(): array
    {
        return [
            'Nomor' => 'integer',
            'Status' => StatusOtomasi::class,
            'DiterbitkanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<OtomasiPemasaran, $this> */
    public function otomasi(): BelongsTo
    {
        return $this->belongsTo(OtomasiPemasaran::class, 'OtomasiPemasaranId', 'Id');
    }

    /** @return HasMany<LangkahOtomasiPemasaran, $this> */
    public function langkah(): HasMany
    {
        return $this->hasMany(LangkahOtomasiPemasaran::class, 'VersiOtomasiPemasaranId', 'Id')
            ->orderBy('Urutan');
    }
}

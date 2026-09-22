<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kelompok keyword yang menggarap satu tema (MARKETING.md 9). */
final class ClusterSeo extends ModelDasar
{
    protected $table = 'ClusterSeo';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Kode', 'Nama', 'Keterangan'];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<KeywordSeo, $this> */
    public function keyword(): HasMany
    {
        return $this->hasMany(KeywordSeo::class, 'ClusterSeoId', 'Id');
    }
}

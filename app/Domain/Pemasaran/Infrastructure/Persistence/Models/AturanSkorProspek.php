<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Bobot satu sinyal terhadap skor prospek (MARKETING.md 5.4, 24). */
final class AturanSkorProspek extends ModelDasar
{
    protected $table = 'AturanSkorProspek';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Peristiwa', 'Bobot', 'Aktif', 'Keterangan'];

    protected function casts(): array
    {
        return [
            'Bobot' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<SkorProspek, $this> */
    public function rincianSkor(): HasMany
    {
        return $this->hasMany(SkorProspek::class, 'AturanSkorProspekId', 'Id');
    }

    /** Dari mana sinyalnya datang. */
    public function asal(): ?string
    {
        return KatalogPeristiwaSkor::asal($this->Peristiwa);
    }

    public function berlaku(): bool
    {
        return $this->Aktif && $this->asal() !== KatalogPeristiwaSkor::ASAL_TERTUNDA;
    }
}

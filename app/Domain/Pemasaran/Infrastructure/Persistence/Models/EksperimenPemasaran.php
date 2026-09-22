<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Domain\Enums\TargetEksperimen;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu eksperimen A/B beserta ambang sampelnya (MARKETING.md 22). */
final class EksperimenPemasaran extends ModelDasar
{
    protected $table = 'EksperimenPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Target',
        'Hipotesis',
        'Status',
        'MetrikUtama',
        'MinimumSampel',
        'PemenangVarianId',
        'AlasanKeputusan',
        'DiputuskanPada',
        'MulaiPada',
        'SelesaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Target' => TargetEksperimen::class,
            'Status' => StatusEksperimen::class,
            'MetrikUtama' => MetrikEksperimen::class,
            'MinimumSampel' => 'integer',
            'DiputuskanPada' => 'immutable_datetime',
            'MulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<VarianEksperimen, $this> */
    public function varian(): HasMany
    {
        return $this->hasMany(VarianEksperimen::class, 'EksperimenPemasaranId', 'Id')->orderBy('Kode');
    }

    /** @return HasMany<PartisipasiEksperimen, $this> */
    public function partisipasi(): HasMany
    {
        return $this->hasMany(PartisipasiEksperimen::class, 'EksperimenPemasaranId', 'Id');
    }

    /** @return HasMany<HasilEksperimen, $this> */
    public function hasil(): HasMany
    {
        return $this->hasMany(HasilEksperimen::class, 'EksperimenPemasaranId', 'Id');
    }

    /** @return BelongsTo<VarianEksperimen, $this> */
    public function pemenang(): BelongsTo
    {
        return $this->belongsTo(VarianEksperimen::class, 'PemenangVarianId', 'Id');
    }
}

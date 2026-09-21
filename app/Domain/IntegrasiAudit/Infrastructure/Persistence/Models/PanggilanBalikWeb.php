<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PanggilanBalikWeb extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PanggilanBalikWeb';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nama',
        'Url',
        'Rahasia',
        'Peristiwa',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Peristiwa' => 'array',
            'Rahasia' => 'encrypted',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return HasMany<PengirimanPanggilanBalikWeb, $this>
     */
    public function pengiriman(): HasMany
    {
        return $this->hasMany(PengirimanPanggilanBalikWeb::class, 'PanggilanBalikWebId', 'Id');
    }
}

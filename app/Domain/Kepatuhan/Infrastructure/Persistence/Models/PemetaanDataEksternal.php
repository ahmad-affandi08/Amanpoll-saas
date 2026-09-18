<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PemetaanDataEksternal extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PemetaanDataEksternal';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'IntegrasiEksternalId',
        'JenisEntitas',
        'EntitasId',
        'KodeEksternal',
        'DataTambahan',
    ];

    protected function casts(): array
    {
        return [
            'DataTambahan' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function integrasiEksternal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal::class, 'IntegrasiEksternalId', 'Id');
    }

}

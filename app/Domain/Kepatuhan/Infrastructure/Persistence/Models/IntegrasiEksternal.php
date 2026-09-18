<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IntegrasiEksternal extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'IntegrasiEksternal';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Jenis',
        'UrlDasar',
        'MetodeAutentikasi',
        'KonfigurasiTerenkripsi',
        'Status',
        'TerakhirSinkronPada',
    ];

    protected function casts(): array
    {
        return [
            'KonfigurasiTerenkripsi' => 'array',
            'TerakhirSinkronPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

}

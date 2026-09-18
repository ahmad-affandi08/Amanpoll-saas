<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Peran extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Peran';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Keterangan',
        'BawaanSistem',
    ];

    protected function casts(): array
    {
        return [
            'BawaanSistem' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

}

<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DasborTersimpan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DasborTersimpan';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nama',
        'PemilikId',
        'Bawaan',
        'Konfigurasi',
    ];

    protected function casts(): array
    {
        return [
            'Bawaan' => 'boolean',
            'Konfigurasi' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PemilikId', 'Id');
    }

}

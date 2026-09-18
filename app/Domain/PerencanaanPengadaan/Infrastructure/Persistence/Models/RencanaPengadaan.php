<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RencanaPengadaan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RencanaPengadaan';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'Nama',
        'Tahun',
        'PosAnggaranId',
        'Status',
        'TotalEstimasi',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Tahun' => 'integer',
            'TotalEstimasi' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran::class, 'PosAnggaranId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}

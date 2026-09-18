<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PosAnggaran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PosAnggaran';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'AnggaranId',
        'IndukId',
        'Kode',
        'Nama',
        'Jumlah',
        'Terpakai',
        'Ditahan',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:2',
            'Terpakai' => 'decimal:2',
            'Ditahan' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function anggaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran::class, 'AnggaranId', 'Id');
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran::class, 'IndukId', 'Id');
    }

}

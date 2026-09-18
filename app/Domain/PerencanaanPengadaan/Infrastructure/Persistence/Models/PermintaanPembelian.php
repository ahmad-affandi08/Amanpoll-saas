<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PermintaanPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PermintaanPembelian';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'UnitOrganisasiId',
        'RencanaPengadaanId',
        'PosAnggaranId',
        'TanggalPermintaan',
        'TanggalDibutuhkan',
        'Prioritas',
        'Status',
        'Alasan',
        'DimintaOleh',
        'TotalEstimasi',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPermintaan' => 'date',
            'TanggalDibutuhkan' => 'date',
            'TotalEstimasi' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    public function rencanaPengadaan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan::class, 'RencanaPengadaanId', 'Id');
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran::class, 'PosAnggaranId', 'Id');
    }

    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DimintaOleh', 'Id');
    }

}

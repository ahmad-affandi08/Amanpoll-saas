<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PermintaanMutasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PermintaanMutasiAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'JenisMutasi',
        'UnitAsalId',
        'UnitTujuanId',
        'LokasiAsalId',
        'LokasiTujuanId',
        'Alasan',
        'Status',
        'DimintaOleh',
        'DimintaPada',
        'DisetujuiPada',
        'SelesaiPada',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'DimintaPada' => 'immutable_datetime',
            'DisetujuiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function unitAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitAsalId', 'Id');
    }

    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitTujuanId', 'Id');
    }

    public function lokasiAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiAsalId', 'Id');
    }

    public function lokasiTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiTujuanId', 'Id');
    }

    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DimintaOleh', 'Id');
    }

}

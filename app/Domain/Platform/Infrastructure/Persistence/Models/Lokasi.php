<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Lokasi extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Lokasi';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'UnitOrganisasiId',
        'KategoriLokasiId',
        'IndukId',
        'Kode',
        'Nama',
        'Alamat',
        'Lantai',
        'Latitude',
        'Longitude',
        'ZonaWaktu',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'Latitude' => 'decimal:7',
            'Longitude' => 'decimal:7',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<UnitOrganisasi, $this>
     */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<KategoriLokasi, $this>
     */
    public function kategoriLokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi::class, 'KategoriLokasiId', 'Id');
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'IndukId', 'Id');
    }

}

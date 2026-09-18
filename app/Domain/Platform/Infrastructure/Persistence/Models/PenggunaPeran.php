<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenggunaPeran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenggunaPeran';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenggunaId',
        'PeranId',
        'UnitOrganisasiId',
        'LokasiId',
        'BerlakuMulai',
        'BerlakuSampai',
    ];

    protected function casts(): array
    {
        return [
            'BerlakuMulai' => 'immutable_datetime',
            'BerlakuSampai' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
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
     * @return BelongsTo<Pengguna, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PenggunaId', 'Id');
    }

    /**
     * @return BelongsTo<Peran, $this>
     */
    public function peran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Peran::class, 'PeranId', 'Id');
    }

    /**
     * @return BelongsTo<UnitOrganisasi, $this>
     */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiId', 'Id');
    }

}

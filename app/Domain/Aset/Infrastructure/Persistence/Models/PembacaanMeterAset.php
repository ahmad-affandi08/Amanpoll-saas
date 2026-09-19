<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PembacaanMeterAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PembacaanMeterAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'MeterAsetId',
        'Nilai',
        'DibacaPada',
        'Sumber',
        'DicatatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Nilai' => 'decimal:4',
            'DibacaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<MeterAset, $this>
     */
    public function meterAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset::class, 'MeterAsetId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna, $this>
     */
    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DicatatOleh', 'Id');
    }

}

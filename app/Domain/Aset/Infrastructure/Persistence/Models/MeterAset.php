<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MeterAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'MeterAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'Nama',
        'Satuan',
        'Jenis',
        'NilaiAwal',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'NilaiAwal' => 'decimal:4',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /**
     * @return HasMany<PembacaanMeterAset, $this>
     */
    public function pembacaan(): HasMany
    {
        return $this->hasMany(PembacaanMeterAset::class, 'MeterAsetId', 'Id')->orderByDesc('DibacaPada');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RencanaPemeliharaanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RencanaPemeliharaanAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'RencanaPemeliharaanId',
        'AsetId',
        'MeterAsetId',
        'TanggalMulai',
        'TanggalBerikutnya',
        'NilaiMeterBerikutnya',
        'TerakhirDilaksanakanPada',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'TanggalMulai' => 'date',
            'TanggalBerikutnya' => 'date',
            'NilaiMeterBerikutnya' => 'decimal:4',
            'TerakhirDilaksanakanPada' => 'immutable_datetime',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<RencanaPemeliharaan, $this> */
    public function rencanaPemeliharaan(): BelongsTo
    {
        return $this->belongsTo(RencanaPemeliharaan::class, 'RencanaPemeliharaanId', 'Id');
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<MeterAset, $this> */
    public function meterAset(): BelongsTo
    {
        return $this->belongsTo(MeterAset::class, 'MeterAsetId', 'Id');
    }

    /** @return HasMany<JadwalPemeliharaan, $this> */
    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPemeliharaan::class, 'RencanaPemeliharaanAsetId', 'Id');
    }
}

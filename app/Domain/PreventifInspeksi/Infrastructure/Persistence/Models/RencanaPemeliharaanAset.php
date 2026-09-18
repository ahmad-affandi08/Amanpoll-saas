<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function rencanaPemeliharaan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan::class, 'RencanaPemeliharaanId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RencanaKalibrasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RencanaKalibrasi';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'JenisKalibrasiId',
        'PenyediaId',
        'IntervalHari',
        'TanggalMulai',
        'TanggalBerikutnya',
        'PeringatanHariSebelum',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'IntervalHari' => 'integer',
            'TanggalMulai' => 'date',
            'TanggalBerikutnya' => 'date',
            'PeringatanHariSebelum' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function jenisKalibrasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

}

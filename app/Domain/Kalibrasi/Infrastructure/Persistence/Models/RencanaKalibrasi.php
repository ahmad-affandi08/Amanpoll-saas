<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    public function jenisKalibrasi(): BelongsTo
    {
        return $this->belongsTo(JenisKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    public function pelaksanaanKalibrasi(): HasMany
    {
        return $this->hasMany(PelaksanaanKalibrasi::class, 'RencanaKalibrasiId', 'Id')->latest('TanggalKalibrasi');
    }
}

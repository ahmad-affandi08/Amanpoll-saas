<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RencanaPemeliharaan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RencanaPemeliharaan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Jenis',
        'TemplatDaftarPeriksaId',
        'Prioritas',
        'StrategiJadwal',
        'IntervalNilai',
        'IntervalSatuan',
        'BerdasarkanMeter',
        'AmbangMeter',
        'ToleransiHari',
        'BuatPerintahKerjaHariSebelum',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'IntervalNilai' => 'integer',
            'BerdasarkanMeter' => 'boolean',
            'AmbangMeter' => 'decimal:4',
            'ToleransiHari' => 'integer',
            'BuatPerintahKerjaHariSebelum' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

    public function aset(): HasMany
    {
        return $this->hasMany(RencanaPemeliharaanAset::class, 'RencanaPemeliharaanId', 'Id');
    }
}

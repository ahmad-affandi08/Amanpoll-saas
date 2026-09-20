<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JenisKalibrasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'JenisKalibrasi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Deskripsi',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function titikUkur(): HasMany
    {
        return $this->hasMany(TitikUkurKalibrasi::class, 'JenisKalibrasiId', 'Id')->orderBy('Urutan');
    }

    public function rencanaKalibrasi(): HasMany
    {
        return $this->hasMany(RencanaKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    public function pelaksanaanKalibrasi(): HasMany
    {
        return $this->hasMany(PelaksanaanKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }
}

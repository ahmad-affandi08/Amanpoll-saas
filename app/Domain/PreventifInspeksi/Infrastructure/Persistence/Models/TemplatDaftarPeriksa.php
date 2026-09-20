<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TemplatDaftarPeriksa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TemplatDaftarPeriksa';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Jenis',
        'KategoriAsetId',
        'ModelAsetId',
        'VersiTemplat',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'VersiTemplat' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(ModelAset::class, 'ModelAsetId', 'Id');
    }

    /** @return HasMany<ButirTemplatDaftarPeriksa, $this> */
    public function butir(): HasMany
    {
        return $this->hasMany(ButirTemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id')->orderBy('Urutan');
    }

    /** @return HasMany<PelaksanaanDaftarPeriksa, $this> */
    public function pelaksanaan(): HasMany
    {
        return $this->hasMany(PelaksanaanDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }
}

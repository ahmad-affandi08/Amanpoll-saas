<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset::class, 'ModelAsetId', 'Id');
    }

}

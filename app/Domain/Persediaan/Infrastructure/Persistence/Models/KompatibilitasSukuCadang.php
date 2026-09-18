<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KompatibilitasSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KompatibilitasSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'SukuCadangId',
        'KategoriAsetId',
        'ModelAsetId',
        'AsetId',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset::class, 'ModelAsetId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ModelAset extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'ModelAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'KategoriAsetId',
        'MerekId',
        'KodeModel',
        'Nama',
        'Produsen',
        'Spesifikasi',
        'IntervalPemeliharaanHari',
        'IntervalKalibrasiHari',
        'UmurManfaatBulan',
    ];

    protected function casts(): array
    {
        return [
            'Spesifikasi' => 'array',
            'IntervalPemeliharaanHari' => 'integer',
            'IntervalKalibrasiHari' => 'integer',
            'UmurManfaatBulan' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
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

    public function merek(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Merek::class, 'MerekId', 'Id');
    }

}

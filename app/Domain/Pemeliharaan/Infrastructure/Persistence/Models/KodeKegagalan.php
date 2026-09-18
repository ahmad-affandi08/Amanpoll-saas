<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KodeKegagalan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KodeKegagalan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'KategoriAsetId',
        'Kode',
        'Nama',
        'Jenis',
        'Keterangan',
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
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset::class, 'KategoriAsetId', 'Id');
    }

}

<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailMutasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailMutasiAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PermintaanMutasiAsetId',
        'AsetId',
        'Status',
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

    public function permintaanMutasiAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset::class, 'PermintaanMutasiAsetId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

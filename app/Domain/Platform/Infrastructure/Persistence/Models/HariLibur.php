<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HariLibur extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'HariLibur';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'LokasiId',
        'Tanggal',
        'Nama',
        'BerulangTahunan',
    ];

    protected function casts(): array
    {
        return [
            'Tanggal' => 'date:Y-m-d',
            'BerulangTahunan' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiId', 'Id');
    }

}

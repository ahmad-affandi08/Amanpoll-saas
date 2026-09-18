<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MeterAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'MeterAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'Nama',
        'Satuan',
        'Jenis',
        'NilaiAwal',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'NilaiAwal' => 'decimal:4',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

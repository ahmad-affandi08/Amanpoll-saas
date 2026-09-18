<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransaksiAnggaran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TransaksiAnggaran';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PosAnggaranId',
        'Jenis',
        'ReferensiJenis',
        'ReferensiId',
        'Jumlah',
        'Tanggal',
        'Keterangan',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:2',
            'Tanggal' => 'date',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran::class, 'PosAnggaranId', 'Id');
    }

}

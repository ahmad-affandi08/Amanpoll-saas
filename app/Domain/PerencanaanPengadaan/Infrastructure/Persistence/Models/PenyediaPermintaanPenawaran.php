<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenyediaPermintaanPenawaran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenyediaPermintaanPenawaran';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PermintaanPenawaranId',
        'PenyediaId',
        'DikirimPada',
        'DilihatPada',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DikirimPada' => 'immutable_datetime',
            'DilihatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function permintaanPenawaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran::class, 'PermintaanPenawaranId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

}

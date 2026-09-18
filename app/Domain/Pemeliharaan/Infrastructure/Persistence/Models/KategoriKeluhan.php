<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KategoriKeluhan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KategoriKeluhan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'IndukId',
        'Kode',
        'Nama',
        'TingkatLayananId',
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

    public function induk(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan::class, 'IndukId', 'Id');
    }

    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

}

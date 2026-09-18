<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TemplatInspeksi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TemplatInspeksi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'KategoriAsetId',
        'TemplatDaftarPeriksaId',
        'IntervalHari',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'IntervalHari' => 'integer',
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

    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

}

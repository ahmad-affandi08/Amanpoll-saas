<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JawabanDaftarPeriksa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'JawabanDaftarPeriksa';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PelaksanaanDaftarPeriksaId',
        'ButirTemplatDaftarPeriksaId',
        'NilaiTeks',
        'NilaiAngka',
        'NilaiBoolean',
        'NilaiTanggal',
        'NilaiJson',
        'Sesuai',
        'Catatan',
        'DijawabPada',
    ];

    protected function casts(): array
    {
        return [
            'NilaiAngka' => 'decimal:6',
            'NilaiBoolean' => 'boolean',
            'NilaiTanggal' => 'immutable_datetime',
            'NilaiJson' => 'array',
            'Sesuai' => 'boolean',
            'DijawabPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function pelaksanaanDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa::class, 'PelaksanaanDaftarPeriksaId', 'Id');
    }

    public function butirTemplatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa::class, 'ButirTemplatDaftarPeriksaId', 'Id');
    }

}

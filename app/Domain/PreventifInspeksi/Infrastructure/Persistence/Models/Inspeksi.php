<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Inspeksi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'Inspeksi';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'TemplatInspeksiId',
        'AsetId',
        'PelaksanaanDaftarPeriksaId',
        'DijadwalkanPada',
        'DilaksanakanPada',
        'Status',
        'Hasil',
        'Temuan',
        'TindakLanjut',
        'PerintahKerjaId',
        'DilaksanakanOleh',
    ];

    protected function casts(): array
    {
        return [
            'DijadwalkanPada' => 'immutable_datetime',
            'DilaksanakanPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function templatInspeksi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi::class, 'TemplatInspeksiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function pelaksanaanDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa::class, 'PelaksanaanDaftarPeriksaId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    public function dilaksanakanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DilaksanakanOleh', 'Id');
    }

}

<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PelaksanaanDaftarPeriksa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PelaksanaanDaftarPeriksa';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TemplatDaftarPeriksaId',
        'PerintahKerjaId',
        'AsetId',
        'DilaksanakanOleh',
        'MulaiPada',
        'SelesaiPada',
        'Status',
        'Skor',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'Skor' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function dilaksanakanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DilaksanakanOleh', 'Id');
    }

}

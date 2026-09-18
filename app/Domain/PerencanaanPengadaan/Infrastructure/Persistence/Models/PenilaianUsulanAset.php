<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenilaianUsulanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenilaianUsulanAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'UsulanAsetId',
        'Kriteria',
        'Bobot',
        'Nilai',
        'Skor',
        'DinilaiOleh',
        'DinilaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Bobot' => 'decimal:4',
            'Nilai' => 'decimal:4',
            'Skor' => 'decimal:4',
            'DinilaiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function usulanAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset::class, 'UsulanAsetId', 'Id');
    }

    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DinilaiOleh', 'Id');
    }

}

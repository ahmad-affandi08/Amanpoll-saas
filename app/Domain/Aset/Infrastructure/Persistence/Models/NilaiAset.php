<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NilaiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'NilaiAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'TanggalNilai',
        'NilaiBuku',
        'AkumulasiPenyusutan',
        'BebanPenyusutanPeriode',
        'Metode',
    ];

    protected function casts(): array
    {
        return [
            'TanggalNilai' => 'date',
            'NilaiBuku' => 'decimal:2',
            'AkumulasiPenyusutan' => 'decimal:2',
            'BebanPenyusutanPeriode' => 'decimal:2',
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

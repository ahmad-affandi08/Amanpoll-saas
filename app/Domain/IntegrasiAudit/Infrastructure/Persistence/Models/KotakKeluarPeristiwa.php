<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KotakKeluarPeristiwa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KotakKeluarPeristiwa';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'NamaPeristiwa',
        'JenisAgregat',
        'AgregatId',
        'MuatanData',
        'Status',
        'Percobaan',
        'TersediaPada',
        'DiprosesPada',
        'KesalahanTerakhir',
    ];

    protected function casts(): array
    {
        return [
            'MuatanData' => 'array',
            'Percobaan' => 'integer',
            'TersediaPada' => 'immutable_datetime',
            'DiprosesPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

}

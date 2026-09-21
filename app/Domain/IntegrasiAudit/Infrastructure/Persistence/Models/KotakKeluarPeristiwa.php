<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KotakKeluarPeristiwa extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KotakKeluarPeristiwa';

    public $timestamps = false;

    /** Setelah percobaan ini habis, peristiwa berhenti dicoba dan menunggu tinjauan manual. */
    public const BATAS_PERCOBAAN = 5;

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

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }
}

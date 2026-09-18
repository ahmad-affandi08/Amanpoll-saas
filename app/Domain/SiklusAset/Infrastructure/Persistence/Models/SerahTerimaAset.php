<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SerahTerimaAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'SerahTerimaAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'PermintaanMutasiAsetId',
        'Jenis',
        'PihakMenyerahkan',
        'PihakMenerima',
        'DiserahkanPada',
        'DiterimaPada',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'DiserahkanPada' => 'immutable_datetime',
            'DiterimaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function permintaanMutasiAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset::class, 'PermintaanMutasiAsetId', 'Id');
    }

    public function pihakMenyerahkan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PihakMenyerahkan', 'Id');
    }

    public function pihakMenerima(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PihakMenerima', 'Id');
    }

}

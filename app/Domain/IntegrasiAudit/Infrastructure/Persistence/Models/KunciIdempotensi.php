<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KunciIdempotensi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KunciIdempotensi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kunci',
        'Rute',
        'HashPermintaan',
        'StatusHttp',
        'Respons',
        'KadaluarsaPada',
    ];

    protected function casts(): array
    {
        return [
            'StatusHttp' => 'integer',
            'KadaluarsaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NomorDokumen extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'NomorDokumen';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'JenisDokumen',
        'Awalan',
        'FormatNomor',
        'NomorTerakhir',
        'ResetPeriode',
        'PeriodeAktif',
    ];

    protected function casts(): array
    {
        return [
            'NomorTerakhir' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NilaiKolomKustom extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'NilaiKolomKustom';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'DefinisiKolomKustomId',
        'JenisEntitas',
        'EntitasId',
        'Nilai',
    ];

    protected function casts(): array
    {
        return [
            'Nilai' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<DefinisiKolomKustom, $this> */
    public function definisiKolomKustom(): BelongsTo
    {
        return $this->belongsTo(DefinisiKolomKustom::class, 'DefinisiKolomKustomId', 'Id');
    }
}

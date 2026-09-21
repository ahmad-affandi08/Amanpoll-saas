<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DefinisiKolomKustom extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DefinisiKolomKustom';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'JenisEntitas',
        'Kode',
        'Label',
        'TipeData',
        'Wajib',
        'Pilihan',
        'AturanValidasi',
        'NilaiBawaan',
        'Urutan',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Wajib' => 'boolean',
            'Pilihan' => 'array',
            'AturanValidasi' => 'array',
            'NilaiBawaan' => 'array',
            'Urutan' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }
}

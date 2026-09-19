<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RiwayatPenanggungJawabAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RiwayatPenanggungJawabAset';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'PenggunaId',
        'UnitOrganisasiId',
        'MulaiPada',
        'SelesaiPada',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PenggunaId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi, $this>
     */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

}

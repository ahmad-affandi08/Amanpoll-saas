<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GaransiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'GaransiAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_AKTIF = 'Aktif';
    public const STATUS_BERAKHIR = 'Berakhir';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'PenyediaId',
        'NomorGaransi',
        'JenisGaransi',
        'MulaiPada',
        'BerakhirPada',
        'Cakupan',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'MulaiPada' => 'date',
            'BerakhirPada' => 'date',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
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
     * @return BelongsTo<\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

}

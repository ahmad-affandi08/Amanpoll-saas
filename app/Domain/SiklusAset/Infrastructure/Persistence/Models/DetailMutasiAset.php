<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailMutasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailMutasiAset';

    public $timestamps = false;

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $fillable = [
        'OrganisasiId',
        'PermintaanMutasiAsetId',
        'AsetId',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<PermintaanMutasiAset, $this>
     */
    public function permintaanMutasiAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset::class, 'PermintaanMutasiAsetId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Aset\Infrastructure\Persistence\Models\Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

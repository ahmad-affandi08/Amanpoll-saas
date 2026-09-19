<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailPenghapusanAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailPenghapusanAset';

    public $timestamps = false;

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $fillable = [
        'OrganisasiId',
        'PengajuanPenghapusanAsetId',
        'AsetId',
        'NilaiBukuSaatPenghapusan',
        'HasilPelepasan',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'NilaiBukuSaatPenghapusan' => 'decimal:2',
            'HasilPelepasan' => 'decimal:2',
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
     * @return BelongsTo<PengajuanPenghapusanAset, $this>
     */
    public function pengajuanPenghapusanAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset::class, 'PengajuanPenghapusanAsetId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Aset\Infrastructure\Persistence\Models\Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

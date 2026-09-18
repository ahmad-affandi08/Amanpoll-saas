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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function pengajuanPenghapusanAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset::class, 'PengajuanPenghapusanAsetId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

}

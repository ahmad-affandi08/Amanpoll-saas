<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailPermintaanPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailPermintaanPembelian';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PermintaanPembelianId',
        'JenisItem',
        'AsetReferensiId',
        'SukuCadangId',
        'Deskripsi',
        'Jumlah',
        'Satuan',
        'HargaEstimasi',
        'Spesifikasi',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaEstimasi' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    public function asetReferensi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetReferensiId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

}

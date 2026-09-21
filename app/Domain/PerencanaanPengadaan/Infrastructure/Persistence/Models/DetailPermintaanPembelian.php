<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
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

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<PermintaanPembelian, $this>
     */
    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(PermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function asetReferensi(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetReferensiId', 'Id');
    }

    /**
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }
}

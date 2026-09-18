<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailPenawaranPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailPenawaranPenyedia';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenawaranPenyediaId',
        'DetailPermintaanPembelianId',
        'Deskripsi',
        'Jumlah',
        'HargaSatuan',
        'Diskon',
        'Pajak',
        'Total',
        'WaktuPengirimanHari',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaSatuan' => 'decimal:2',
            'Diskon' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Total' => 'decimal:2',
            'WaktuPengirimanHari' => 'integer',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function penawaranPenyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia::class, 'PenawaranPenyediaId', 'Id');
    }

    public function detailPermintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPermintaanPembelian::class, 'DetailPermintaanPembelianId', 'Id');
    }

}

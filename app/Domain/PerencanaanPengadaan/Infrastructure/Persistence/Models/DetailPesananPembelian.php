<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailPesananPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailPesananPembelian';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PesananPembelianId',
        'JenisItem',
        'SukuCadangId',
        'Deskripsi',
        'Jumlah',
        'Satuan',
        'HargaSatuan',
        'Diskon',
        'Pajak',
        'Total',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaSatuan' => 'decimal:2',
            'Diskon' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Total' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function pesananPembelian(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian::class, 'PesananPembelianId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

}

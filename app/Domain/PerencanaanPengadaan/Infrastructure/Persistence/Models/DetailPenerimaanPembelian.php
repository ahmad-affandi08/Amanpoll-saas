<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailPenerimaanPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailPenerimaanPembelian';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenerimaanPembelianId',
        'DetailPesananPembelianId',
        'SukuCadangId',
        'JumlahDipesan',
        'JumlahDiterima',
        'JumlahDitolak',
        'Kondisi',
        'NomorSeriJson',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'JumlahDipesan' => 'decimal:4',
            'JumlahDiterima' => 'decimal:4',
            'JumlahDitolak' => 'decimal:4',
            'NomorSeriJson' => 'array',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function penerimaanPembelian(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian::class, 'PenerimaanPembelianId', 'Id');
    }

    public function detailPesananPembelian(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian::class, 'DetailPesananPembelianId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

}

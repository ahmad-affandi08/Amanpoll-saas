<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenerimaanPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenerimaanPembelian';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'PesananPembelianId',
        'GudangId',
        'TanggalTerima',
        'NomorSuratJalan',
        'DiterimaOleh',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'TanggalTerima' => 'immutable_datetime',
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

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang::class, 'GudangId', 'Id');
    }

    public function diterimaOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiterimaOleh', 'Id');
    }

}

<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
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

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<PenerimaanPembelian, $this>
     */
    public function penerimaanPembelian(): BelongsTo
    {
        return $this->belongsTo(PenerimaanPembelian::class, 'PenerimaanPembelianId', 'Id');
    }

    /**
     * @return BelongsTo<DetailPesananPembelian, $this>
     */
    public function detailPesananPembelian(): BelongsTo
    {
        return $this->belongsTo(DetailPesananPembelian::class, 'DetailPesananPembelianId', 'Id');
    }

    /**
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }
}

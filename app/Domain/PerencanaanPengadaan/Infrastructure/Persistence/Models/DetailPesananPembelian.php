<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
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

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<PesananPembelian, $this>
     */
    public function pesananPembelian(): BelongsTo
    {
        return $this->belongsTo(PesananPembelian::class, 'PesananPembelianId', 'Id');
    }

    /**
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailRencanaPengadaan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailRencanaPengadaan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'RencanaPengadaanId',
        'UsulanAsetId',
        'SukuCadangId',
        'Deskripsi',
        'Jumlah',
        'Satuan',
        'HargaEstimasi',
        'BulanRencana',
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
     * @return BelongsTo<RencanaPengadaan, $this>
     */
    public function rencanaPengadaan(): BelongsTo
    {
        return $this->belongsTo(RencanaPengadaan::class, 'RencanaPengadaanId', 'Id');
    }

    /**
     * @return BelongsTo<UsulanAset, $this>
     */
    public function usulanAset(): BelongsTo
    {
        return $this->belongsTo(UsulanAset::class, 'UsulanAsetId', 'Id');
    }

    /**
     * @return BelongsTo<SukuCadang, $this>
     */
    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(SukuCadang::class, 'SukuCadangId', 'Id');
    }
}

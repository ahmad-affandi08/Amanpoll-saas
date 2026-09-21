<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PermintaanPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PermintaanPembelian';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_MENUNGGU_PERSETUJUAN = 'MenungguPersetujuan';

    public const STATUS_DISETUJUI = 'Disetujui';

    public const STATUS_DITOLAK = 'Ditolak';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'UnitOrganisasiId',
        'RencanaPengadaanId',
        'PosAnggaranId',
        'TanggalPermintaan',
        'TanggalDibutuhkan',
        'Prioritas',
        'Status',
        'Alasan',
        'DimintaOleh',
        'TotalEstimasi',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPermintaan' => 'date',
            'TanggalDibutuhkan' => 'date',
            'TotalEstimasi' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
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
     * @return BelongsTo<UnitOrganisasi, $this>
     */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<RencanaPengadaan, $this>
     */
    public function rencanaPengadaan(): BelongsTo
    {
        return $this->belongsTo(RencanaPengadaan::class, 'RencanaPengadaanId', 'Id');
    }

    /**
     * @return BelongsTo<PosAnggaran, $this>
     */
    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'PosAnggaranId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DimintaOleh', 'Id');
    }

    /**
     * @return HasMany<DetailPermintaanPembelian, $this>
     */
    public function detail(): HasMany
    {
        return $this->hasMany(DetailPermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    /**
     * @return HasMany<PermintaanPenawaran, $this>
     */
    public function permintaanPenawaran(): HasMany
    {
        return $this->hasMany(PermintaanPenawaran::class, 'PermintaanPembelianId', 'Id');
    }
}

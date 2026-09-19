<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PermintaanMutasiAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PermintaanMutasiAset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';
    public const STATUS_DIBATALKAN = 'Dibatalkan';
    public const STATUS_SELESAI = 'Selesai';

    public const JENIS_ANTAR_LOKASI = 'AntarLokasi';
    public const JENIS_ANTAR_UNIT = 'AntarUnit';
    public const JENIS_PEMINJAMAN = 'Peminjaman';
    public const JENIS_PENGEMBALIAN = 'Pengembalian';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'JenisMutasi',
        'UnitAsalId',
        'UnitTujuanId',
        'LokasiAsalId',
        'LokasiTujuanId',
        'Alasan',
        'Status',
        'DimintaOleh',
        'DimintaPada',
        'DisetujuiPada',
        'SelesaiPada',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'DimintaPada' => 'immutable_datetime',
            'DisetujuiPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi, $this>
     */
    public function unitAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitAsalId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi, $this>
     */
    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitTujuanId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi, $this>
     */
    public function lokasiAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiAsalId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi, $this>
     */
    public function lokasiTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiTujuanId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna, $this>
     */
    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DimintaOleh', 'Id');
    }

    /**
     * @return HasMany<DetailMutasiAset, $this>
     */
    public function detailMutasiAset(): HasMany
    {
        return $this->hasMany(DetailMutasiAset::class, 'PermintaanMutasiAsetId', 'Id');
    }

}

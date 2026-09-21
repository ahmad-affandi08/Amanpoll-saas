<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UsulanAset extends ModelDasar
{
    use MilikOrganisasi;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIAJUKAN = 'Diajukan';

    public const STATUS_MENUNGGU_PERSETUJUAN = 'MenungguPersetujuan';

    public const STATUS_DISETUJUI = 'Disetujui';

    public const STATUS_DITOLAK = 'Ditolak';

    public const PRIORITAS_RENDAH = 'Rendah';

    public const PRIORITAS_NORMAL = 'Normal';

    public const PRIORITAS_TINGGI = 'Tinggi';

    public const PRIORITAS_KRITIS = 'Kritis';

    public const DAFTAR_PRIORITAS = [
        self::PRIORITAS_RENDAH,
        self::PRIORITAS_NORMAL,
        self::PRIORITAS_TINGGI,
        self::PRIORITAS_KRITIS,
    ];

    protected $table = 'UsulanAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'UnitOrganisasiId',
        'KategoriAsetId',
        'ModelAsetId',
        'NamaKebutuhan',
        'Jumlah',
        'EstimasiHargaSatuan',
        'Alasan',
        'JenisKebutuhan',
        'TahunKebutuhan',
        'Prioritas',
        'Status',
        'DiajukanOleh',
        'DiajukanPada',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'EstimasiHargaSatuan' => 'decimal:2',
            'TahunKebutuhan' => 'integer',
            'DiajukanPada' => 'immutable_datetime',
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
     * @return BelongsTo<KategoriAset, $this>
     */
    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    /**
     * @return BelongsTo<ModelAset, $this>
     */
    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(ModelAset::class, 'ModelAsetId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiajukanOleh', 'Id');
    }

    /**
     * @return HasMany<PenilaianUsulanAset, $this>
     */
    public function penilaian(): HasMany
    {
        return $this->hasMany(PenilaianUsulanAset::class, 'UsulanAsetId', 'Id')->orderBy('DinilaiPada');
    }

    /**
     * @return HasMany<DetailRencanaPengadaan, $this>
     */
    public function detailRencanaPengadaan(): HasMany
    {
        return $this->hasMany(DetailRencanaPengadaan::class, 'UsulanAsetId', 'Id');
    }
}

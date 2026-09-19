<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Aset extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Aset';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    public const STATUS_AKTIF = 'Aktif';
    public const STATUS_NONAKTIF = 'Nonaktif';
    public const STATUS_DIPINJAM = 'Dipinjam';
    public const STATUS_RUSAK = 'Rusak';
    public const STATUS_DIARSIPKAN = 'Diarsipkan';

    public const KONDISI_BAIK = 'Baik';
    public const KONDISI_PERLU_PERHATIAN = 'PerluPerhatian';
    public const KONDISI_RUSAK = 'Rusak';

    public const KRITIS_NORMAL = 'Normal';
    public const KRITIS_TINGGI = 'Tinggi';
    public const KRITIS_SANGAT_TINGGI = 'SangatTinggi';

    protected $fillable = [
        'OrganisasiId',
        'UnitOrganisasiId',
        'LokasiId',
        'KategoriAsetId',
        'ModelAsetId',
        'PenyediaId',
        'KodeAset',
        'Nama',
        'NomorSeri',
        'NomorInventaris',
        'NomorRegistrasiEksternal',
        'TanggalPerolehan',
        'TanggalMulaiOperasi',
        'TanggalAkhirOperasi',
        'HargaPerolehan',
        'NilaiResidu',
        'MataUang',
        'SumberDana',
        'MetodePenyusutan',
        'UmurManfaatBulan',
        'Status',
        'Kondisi',
        'TingkatKritis',
        'KodeQr',
        'NfcUid',
        'KodeBatang',
        'Catatan',
        'Versi',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPerolehan' => 'date',
            'TanggalMulaiOperasi' => 'date',
            'TanggalAkhirOperasi' => 'date',
            'HargaPerolehan' => 'decimal:2',
            'NilaiResidu' => 'decimal:2',
            'UmurManfaatBulan' => 'integer',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
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
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiId', 'Id');
    }

    /**
     * @return BelongsTo<KategoriAset, $this>
     */
    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    /**
     * @return BelongsTo<ModelAset, $this>
     */
    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset::class, 'ModelAsetId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsTo<\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

    /**
     * @return HasMany<RiwayatLokasiAset, $this>
     */
    public function riwayatLokasi(): HasMany
    {
        return $this->hasMany(RiwayatLokasiAset::class, 'AsetId', 'Id')->orderByDesc('DipindahkanPada');
    }

    /**
     * @return HasMany<RiwayatPenanggungJawabAset, $this>
     */
    public function riwayatPenanggungJawab(): HasMany
    {
        return $this->hasMany(RiwayatPenanggungJawabAset::class, 'AsetId', 'Id')->orderByDesc('MulaiPada');
    }

    /**
     * @return HasMany<RelasiAset, $this>
     */
    public function relasiSebagaiInduk(): HasMany
    {
        return $this->hasMany(RelasiAset::class, 'AsetIndukId', 'Id');
    }

    /**
     * @return HasMany<RelasiAset, $this>
     */
    public function relasiSebagaiAnak(): HasMany
    {
        return $this->hasMany(RelasiAset::class, 'AsetAnakId', 'Id');
    }

    /**
     * @return HasMany<GaransiAset, $this>
     */
    public function garansiAset(): HasMany
    {
        return $this->hasMany(GaransiAset::class, 'AsetId', 'Id')->orderByDesc('BerakhirPada');
    }

    /**
     * @return HasMany<NilaiAset, $this>
     */
    public function nilaiAset(): HasMany
    {
        return $this->hasMany(NilaiAset::class, 'AsetId', 'Id')->orderByDesc('TanggalNilai');
    }

    /**
     * @return HasMany<MeterAset, $this>
     */
    public function meterAset(): HasMany
    {
        return $this->hasMany(MeterAset::class, 'AsetId', 'Id');
    }

}

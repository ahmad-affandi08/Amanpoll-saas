<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Models;

use App\Core\Izin\BerlingkupUnit;
use App\Core\Izin\DibatasiLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Aset extends ModelDasar implements BerlingkupUnit
{
    use DibatasiLingkup, MilikOrganisasi, PunyaKodeOtomatis, SoftDeletes;

    protected $table = 'Aset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

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

    public function awalanKode(): string
    {
        return 'AST';
    }

    public function kolomKode(): string
    {
        return 'KodeAset';
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
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiId', 'Id');
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
     * @return BelongsTo<Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
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

    /**
     * @return HasMany<RencanaKalibrasi, $this>
     */
    public function rencanaKalibrasi(): HasMany
    {
        return $this->hasMany(RencanaKalibrasi::class, 'AsetId', 'Id');
    }

    /**
     * @return HasMany<PelaksanaanKalibrasi, $this>
     */
    public function pelaksanaanKalibrasi(): HasMany
    {
        return $this->hasMany(PelaksanaanKalibrasi::class, 'AsetId', 'Id')->latest('TanggalKalibrasi');
    }

    /**
     * @return HasMany<Keluhan, $this>
     */
    public function keluhan(): HasMany
    {
        return $this->hasMany(Keluhan::class, 'AsetId', 'Id')->latest('DilaporkanPada');
    }

    /**
     * Satu perintah kerja dapat menyentuh beberapa aset, jadi relasinya lewat tabel antara.
     *
     * @return BelongsToMany<PerintahKerja, $this>
     */
    public function perintahKerja(): BelongsToMany
    {
        return $this->belongsToMany(PerintahKerja::class, 'PerintahKerjaAset', 'AsetId', 'PerintahKerjaId')
            ->withPivot(['Utama', 'KondisiAwal', 'KondisiAkhir'])
            ->latest('PerintahKerja.DibuatPada');
    }

    /**
     * @return HasMany<Inspeksi, $this>
     */
    public function inspeksi(): HasMany
    {
        return $this->hasMany(Inspeksi::class, 'AsetId', 'Id')->latest('DijadwalkanPada');
    }

    /**
     * @return HasMany<WaktuHentiAset, $this>
     */
    public function waktuHenti(): HasMany
    {
        return $this->hasMany(WaktuHentiAset::class, 'AsetId', 'Id')->latest('MulaiPada');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Izin\BerlingkupUnit;
use App\Core\Izin\DibatasiLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PerintahKerja extends ModelDasar implements BerlingkupUnit
{
    use DibatasiLingkup, MilikOrganisasi, SoftDeletes;

    protected $attributes = [
        'Prioritas' => 'Normal',
        'Status' => StatusPerintahKerja::Draf->value,
        'PersentaseSelesai' => 0,
        'MembutuhkanWaktuHenti' => false,
        'MembutuhkanPersetujuan' => false,
        'Versi' => 1,
    ];

    protected $table = 'PerintahKerja';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'KeluhanId',
        'TingkatLayananId',
        'Jenis',
        'Judul',
        'Deskripsi',
        'Prioritas',
        'Status',
        'LokasiId',
        'UnitOrganisasiId',
        'DijadwalkanMulaiPada',
        'DijadwalkanSelesaiPada',
        'DiterimaPada',
        'DimulaiPada',
        'DiselesaikanPada',
        'DitutupPada',
        'BatasResponsPada',
        'BatasPenyelesaianPada',
        'PersentaseSelesai',
        'MembutuhkanWaktuHenti',
        'MembutuhkanPersetujuan',
        'RingkasanPenyelesaian',
        'DibuatOleh',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'DijadwalkanMulaiPada' => 'immutable_datetime',
            'DijadwalkanSelesaiPada' => 'immutable_datetime',
            'DiterimaPada' => 'immutable_datetime',
            'DimulaiPada' => 'immutable_datetime',
            'DiselesaikanPada' => 'immutable_datetime',
            'DitutupPada' => 'immutable_datetime',
            'BatasResponsPada' => 'immutable_datetime',
            'BatasPenyelesaianPada' => 'immutable_datetime',
            'PersentaseSelesai' => 'decimal:2',
            'MembutuhkanWaktuHenti' => 'boolean',
            'MembutuhkanPersetujuan' => 'boolean',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Keluhan, $this> */
    public function keluhan(): BelongsTo
    {
        return $this->belongsTo(Keluhan::class, 'KeluhanId', 'Id');
    }

    /** @return BelongsTo<TingkatLayanan, $this> */
    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return BelongsTo<Lokasi, $this> */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiId', 'Id');
    }

    /** @return BelongsTo<UnitOrganisasi, $this> */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }

    /** @return HasMany<PerintahKerjaAset, $this> */
    public function asetPekerjaan(): HasMany
    {
        return $this->hasMany(PerintahKerjaAset::class, 'PerintahKerjaId', 'Id');
    }

    /** @return BelongsToMany<Aset, $this> */
    public function aset(): BelongsToMany
    {
        return $this->belongsToMany(Aset::class, 'PerintahKerjaAset', 'PerintahKerjaId', 'AsetId')
            ->withPivot(['Id', 'Utama', 'KondisiAwal', 'KondisiAkhir']);
    }

    /** @return HasMany<PenugasanPerintahKerja, $this> */
    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanPerintahKerja::class, 'PerintahKerjaId', 'Id')->latest('DitugaskanPada');
    }

    /** @return HasMany<RiwayatStatusPerintahKerja, $this> */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPerintahKerja::class, 'PerintahKerjaId', 'Id')->oldest('DiubahPada');
    }

    /** @return HasMany<WaktuKerja, $this> */
    public function waktuKerja(): HasMany
    {
        return $this->hasMany(WaktuKerja::class, 'PerintahKerjaId', 'Id')->latest('MulaiPada');
    }

    /** @return HasMany<WaktuHentiAset, $this> */
    public function waktuHenti(): HasMany
    {
        return $this->hasMany(WaktuHentiAset::class, 'PerintahKerjaId', 'Id')->latest('MulaiPada');
    }

    /** @return HasMany<BiayaPerintahKerja, $this> */
    public function biaya(): HasMany
    {
        return $this->hasMany(BiayaPerintahKerja::class, 'PerintahKerjaId', 'Id')->latest('TanggalBiaya');
    }

    /** @return HasOne<AnalisisKegagalan, $this> */
    public function analisisKegagalan(): HasOne
    {
        return $this->hasOne(AnalisisKegagalan::class, 'PerintahKerjaId', 'Id');
    }

    /** @return HasMany<ReservasiSukuCadang, $this> */
    public function reservasiSukuCadang(): HasMany
    {
        return $this->hasMany(ReservasiSukuCadang::class, 'PerintahKerjaId', 'Id')->latest('DibuatPada');
    }

    /** @return HasMany<PemakaianSukuCadang, $this> */
    public function pemakaianSukuCadang(): HasMany
    {
        return $this->hasMany(PemakaianSukuCadang::class, 'PerintahKerjaId', 'Id')->latest('DipakaiPada');
    }

    /** @return HasMany<PelaksanaanDaftarPeriksa, $this> */
    public function pelaksanaanDaftarPeriksa(): HasMany
    {
        return $this->hasMany(PelaksanaanDaftarPeriksa::class, 'PerintahKerjaId', 'Id');
    }
}

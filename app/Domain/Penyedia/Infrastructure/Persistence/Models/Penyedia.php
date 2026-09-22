<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Penyedia extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis, SoftDeletes;

    protected $table = 'Penyedia';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'NamaLegal',
        'NomorIdentitasPajak',
        'Email',
        'Telepon',
        'Website',
        'Alamat',
        'Kota',
        'Provinsi',
        'Negara',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'PYD';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return HasMany<KontakPenyedia, $this>
     */
    public function kontakPenyedia(): HasMany
    {
        return $this->hasMany(KontakPenyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return HasMany<PenilaianPenyedia, $this>
     */
    public function penilaianPenyedia(): HasMany
    {
        return $this->hasMany(PenilaianPenyedia::class, 'PenyediaId', 'Id')->orderByDesc('PeriodeMulai');
    }

    /**
     * @return HasMany<PenyediaKategori, $this>
     */
    public function penyediaKategori(): HasMany
    {
        return $this->hasMany(PenyediaKategori::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsToMany<KategoriPenyedia, $this>
     */
    public function kategoriPenyedia(): BelongsToMany
    {
        return $this->belongsToMany(KategoriPenyedia::class, 'PenyediaKategori', 'PenyediaId', 'KategoriPenyediaId')
            ->withPivot('Id');
    }

    /**
     * @return HasMany<PenawaranPenyedia, $this>
     */
    public function penawaran(): HasMany
    {
        return $this->hasMany(PenawaranPenyedia::class, 'PenyediaId', 'Id')->orderByDesc('TanggalPenawaran');
    }

    /**
     * @return HasMany<PesananPembelian, $this>
     */
    public function pesananPembelian(): HasMany
    {
        return $this->hasMany(PesananPembelian::class, 'PenyediaId', 'Id')->orderByDesc('TanggalPesanan');
    }

    /**
     * @return HasMany<TagihanPenyedia, $this>
     */
    public function tagihan(): HasMany
    {
        return $this->hasMany(TagihanPenyedia::class, 'PenyediaId', 'Id')->orderByDesc('TanggalTagihan');
    }

    /**
     * @return HasMany<Kontrak, $this>
     */
    public function kontrak(): HasMany
    {
        return $this->hasMany(Kontrak::class, 'PenyediaId', 'Id')->orderByDesc('MulaiPada');
    }

    /**
     * Aset yang tercatat dibeli dari penyedia ini.
     *
     * @return HasMany<Aset, $this>
     */
    public function asetDipasok(): HasMany
    {
        return $this->hasMany(Aset::class, 'PenyediaId', 'Id')->orderBy('Nama');
    }

    /**
     * @return HasMany<PelaksanaanKalibrasi, $this>
     */
    public function pelaksanaanKalibrasi(): HasMany
    {
        return $this->hasMany(PelaksanaanKalibrasi::class, 'PenyediaId', 'Id')->orderByDesc('TanggalKalibrasi');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Izin\BerlingkupUnit;
use App\Core\Izin\DibatasiLingkup;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\UrgensiPelapor;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Keluhan extends ModelDasar implements BerlingkupUnit
{
    use DibatasiLingkup, MilikOrganisasi, SoftDeletes;

    protected $attributes = [
        'Prioritas' => PrioritasKeluhan::Normal->value,
        'Status' => StatusKeluhan::Baru->value,
        'Sumber' => 'Web',
        'Versi' => 1,
    ];

    protected $table = 'Keluhan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'KategoriKeluhanId',
        'TingkatLayananId',
        'AsetId',
        'LokasiId',
        'UnitPengelolaId',
        'Judul',
        'Deskripsi',
        'Prioritas',
        'UsulanUrgensi',
        'Status',
        'Sumber',
        'PelaporId',
        'NamaPelaporEksternal',
        'KontakPelaporEksternal',
        'DilaporkanPada',
        'DiresponsPada',
        'BatasResponsPada',
        'BatasPenyelesaianPada',
        'DiresolusikanPada',
        'DitutupPada',
        'Rating',
        'Ulasan',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'UsulanUrgensi' => UrgensiPelapor::class,
            'DilaporkanPada' => 'immutable_datetime',
            'DiresponsPada' => 'immutable_datetime',
            'BatasResponsPada' => 'immutable_datetime',
            'BatasPenyelesaianPada' => 'immutable_datetime',
            'DiresolusikanPada' => 'immutable_datetime',
            'DitutupPada' => 'immutable_datetime',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    /**
     * Keluhan dibatasi ruangannya dan unit pengelolanya.
     *
     * `UnitPengelolaId` hanya memperluas: pengguna berlingkup unit IT ikut melihat
     * baris yang dipelihara IT di ruangan mana pun (PRD 8.21).
     *
     * @return array<string, 'unit'|'lokasi'>
     */
    public function kolomLingkup(): array
    {
        return ['LokasiId' => 'lokasi', 'UnitPengelolaId' => 'unit'];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<KategoriKeluhan, $this> */
    public function kategoriKeluhan(): BelongsTo
    {
        return $this->belongsTo(KategoriKeluhan::class, 'KategoriKeluhanId', 'Id');
    }

    /** @return BelongsTo<TingkatLayanan, $this> */
    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<Lokasi, $this> */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PelaporId', 'Id');
    }

    /** @return HasMany<RiwayatStatusKeluhan, $this> */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusKeluhan::class, 'KeluhanId', 'Id')->oldest('DiubahPada');
    }

    /**
     * Bagian yang memelihara baris ini (PRD 8.21); UnitOrganisasi bertanda MengelolaAset.
     *
     * Lepas dari ScopeLingkup (tenancy tetap berlaku): pengguna berlingkup ruangan
     * yang melihat baris ini harus tetap membaca nama unit pengelolanya, walau
     * unit itu sendiri di luar lingkupnya.
     *
     * @return BelongsTo<UnitOrganisasi, $this>
     */
    public function unitPengelola(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'UnitPengelolaId', 'Id')
            ->withoutGlobalScope(ScopeLingkup::class);
    }
}

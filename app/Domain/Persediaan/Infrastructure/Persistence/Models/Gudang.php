<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Izin\BerlingkupUnit;
use App\Core\Izin\DibatasiLingkup;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Gudang extends ModelDasar implements BerlingkupUnit
{
    use DibatasiLingkup, MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'Gudang';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'LokasiId',
        'UnitPengelolaId',
        'Kode',
        'Nama',
        'PenanggungJawabId',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /**
     * Gudang dibatasi ruangannya dan unit pengelolanya.
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

    public function awalanKode(): string
    {
        return 'GDG';
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<Lokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'LokasiId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PenanggungJawabId', 'Id');
    }

    /**
     * @return HasMany<LokasiGudang, $this>
     */
    public function lokasiGudang(): HasMany
    {
        return $this->hasMany(LokasiGudang::class, 'GudangId', 'Id');
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

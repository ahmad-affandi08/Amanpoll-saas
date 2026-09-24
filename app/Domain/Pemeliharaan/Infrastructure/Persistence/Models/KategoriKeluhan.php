<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class KategoriKeluhan extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'KategoriKeluhan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'IndukId',
        'Kode',
        'Nama',
        'TingkatLayananId',
        'PrioritasBawaan',
        'AsetWajib',
        'PeranPenanggungJawabId',
        'UnitPengelolaId',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'AsetWajib' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'KKL';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<KategoriKeluhan, $this> */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(KategoriKeluhan::class, 'IndukId', 'Id');
    }

    /** @return BelongsTo<TingkatLayanan, $this> */
    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return BelongsTo<Peran, $this> */
    public function peranPenanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'PeranPenanggungJawabId', 'Id');
    }

    /** @return HasMany<KategoriKeluhan, $this> */
    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'IndukId', 'Id');
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

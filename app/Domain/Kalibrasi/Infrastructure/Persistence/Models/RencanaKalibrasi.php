<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RencanaKalibrasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RencanaKalibrasi';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'UnitPengelolaId',
        'JenisKalibrasiId',
        'PenyediaId',
        'IntervalHari',
        'TanggalMulai',
        'TanggalBerikutnya',
        'PeringatanHariSebelum',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'IntervalHari' => 'integer',
            'TanggalMulai' => 'date',
            'TanggalBerikutnya' => 'date',
            'PeringatanHariSebelum' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<JenisKalibrasi, $this> */
    public function jenisKalibrasi(): BelongsTo
    {
        return $this->belongsTo(JenisKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    /** @return BelongsTo<Penyedia, $this> */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    /** @return HasMany<PelaksanaanKalibrasi, $this> */
    public function pelaksanaanKalibrasi(): HasMany
    {
        return $this->hasMany(PelaksanaanKalibrasi::class, 'RencanaKalibrasiId', 'Id')->latest('TanggalKalibrasi');
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

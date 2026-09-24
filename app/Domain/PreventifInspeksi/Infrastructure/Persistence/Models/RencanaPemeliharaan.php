<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RencanaPemeliharaan extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'RencanaPemeliharaan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Jenis',
        'TemplatDaftarPeriksaId',
        'UnitPengelolaId',
        'Prioritas',
        'StrategiJadwal',
        'IntervalNilai',
        'IntervalSatuan',
        'BerdasarkanMeter',
        'AmbangMeter',
        'ToleransiHari',
        'BuatPerintahKerjaHariSebelum',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'IntervalNilai' => 'integer',
            'BerdasarkanMeter' => 'boolean',
            'AmbangMeter' => 'decimal:4',
            'ToleransiHari' => 'integer',
            'BuatPerintahKerjaHariSebelum' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'RPM';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<TemplatDaftarPeriksa, $this> */
    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

    /** @return HasMany<RencanaPemeliharaanAset, $this> */
    public function aset(): HasMany
    {
        return $this->hasMany(RencanaPemeliharaanAset::class, 'RencanaPemeliharaanId', 'Id');
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

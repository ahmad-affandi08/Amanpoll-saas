<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TemplatInspeksi extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'TemplatInspeksi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'KategoriAsetId',
        'TemplatDaftarPeriksaId',
        'IntervalHari',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'IntervalHari' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'TIN';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<KategoriAset, $this> */
    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    /** @return BelongsTo<TemplatDaftarPeriksa, $this> */
    public function templatDaftarPeriksa(): BelongsTo
    {
        return $this->belongsTo(TemplatDaftarPeriksa::class, 'TemplatDaftarPeriksaId', 'Id');
    }

    /** @return HasMany<Inspeksi, $this> */
    public function inspeksi(): HasMany
    {
        return $this->hasMany(Inspeksi::class, 'TemplatInspeksiId', 'Id');
    }
}

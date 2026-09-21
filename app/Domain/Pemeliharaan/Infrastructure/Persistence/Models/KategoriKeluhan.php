<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class KategoriKeluhan extends ModelDasar
{
    use MilikOrganisasi;

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
}

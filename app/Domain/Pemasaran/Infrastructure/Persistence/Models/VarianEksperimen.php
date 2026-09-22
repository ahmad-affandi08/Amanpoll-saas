<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu varian yang diuji; bobotnya menentukan seberapa sering ia ditetapkan (MARKETING.md 22). */
final class VarianEksperimen extends ModelDasar
{
    protected $table = 'VarianEksperimen';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'EksperimenPemasaranId',
        'Kode',
        'Nama',
        'Bobot',
        'Kontrol',
        'Konfigurasi',
    ];

    protected function casts(): array
    {
        return [
            'Bobot' => 'integer',
            'Kontrol' => 'boolean',
            'Konfigurasi' => 'array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EksperimenPemasaran, $this> */
    public function eksperimen(): BelongsTo
    {
        return $this->belongsTo(EksperimenPemasaran::class, 'EksperimenPemasaranId', 'Id');
    }

    /** @return HasMany<PartisipasiEksperimen, $this> */
    public function partisipasi(): HasMany
    {
        return $this->hasMany(PartisipasiEksperimen::class, 'VarianEksperimenId', 'Id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu demo produk beserta setelannya (MARKETING.md 11). */
final class DemoPemasaran extends ModelDasar
{
    protected $table = 'DemoPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Aktif',
        'Dataset',
        'OrganisasiDemoId',
        'ResetIntervalMenit',
        'ModulTampil',
        'FiturDibatasi',
        'CtaLabel',
        'CtaUrl',
        'MaksDurasiMenit',
        'MaksSesiSerentak',
        'TerakhirResetPada',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'ModulTampil' => 'array',
            'FiturDibatasi' => 'array',
            'ResetIntervalMenit' => 'integer',
            'MaksDurasiMenit' => 'integer',
            'MaksSesiSerentak' => 'integer',
            'TerakhirResetPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<SesiDemo, $this> */
    public function sesi(): HasMany
    {
        return $this->hasMany(SesiDemo::class, 'DemoPemasaranId', 'Id');
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasiDemo(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiDemoId', 'Id');
    }
}

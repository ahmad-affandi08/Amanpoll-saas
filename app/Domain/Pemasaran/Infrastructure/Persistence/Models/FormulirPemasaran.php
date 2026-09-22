<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Formulir pemasaran beserta konfigurasinya (MARKETING.md 10). */
final class FormulirPemasaran extends ModelDasar
{
    protected $table = 'FormulirPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'PesanSukses',
        'UrlRedirect',
        'Sumber',
        'KampanyeId',
        'Tag',
        'PemicuOtomasi',
        'UrlWebhook',
        'WajibPersetujuan',
        'CaptchaAktif',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Tag' => 'array',
            'WajibPersetujuan' => 'boolean',
            'CaptchaAktif' => 'boolean',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }

    /** @return HasMany<FieldFormulirPemasaran, $this> */
    public function field(): HasMany
    {
        return $this->hasMany(FieldFormulirPemasaran::class, 'FormulirPemasaranId', 'Id')
            ->orderBy('Urutan');
    }

    /** @return HasMany<PengirimanFormulir, $this> */
    public function pengiriman(): HasMany
    {
        return $this->hasMany(PengirimanFormulir::class, 'FormulirPemasaranId', 'Id');
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}

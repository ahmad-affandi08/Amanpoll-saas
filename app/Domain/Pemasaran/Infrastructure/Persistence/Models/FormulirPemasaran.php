<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Formulir pemasaran beserta konfigurasinya (MARKETING.md 10). */
final class FormulirPemasaran extends ModelDasar
{
    use PunyaKodeOtomatis;

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
        'BerkasLokasi',
        'BerkasNamaAsli',
        'BerkasMime',
        'BerkasUkuranByte',
        'WajibPersetujuan',
        'CaptchaAktif',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Tag' => 'array',
            'BerkasUkuranByte' => 'integer',
            'WajibPersetujuan' => 'boolean',
            'CaptchaAktif' => 'boolean',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'FRM';
    }

    /** Formulir lead magnet adalah formulir biasa yang kebetulan menjanjikan berkas. */
    public function punyaBerkas(): bool
    {
        return $this->BerkasLokasi !== null;
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

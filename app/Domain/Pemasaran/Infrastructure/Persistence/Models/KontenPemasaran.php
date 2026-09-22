<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu konten CMS beserta versinya (MARKETING.md 9). */
final class KontenPemasaran extends ModelDasar
{
    protected $table = 'KontenPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Slug',
        'Jenis',
        'Judul',
        'Status',
        'PenulisNama',
        'KampanyeId',
        'VersiTerbitId',
        'VersiDrafId',
        'NoIndex',
        'TerbitPada',
        'TarikPada',
    ];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisKontenPemasaran::class,
            'Status' => StatusHalamanPemasaran::class,
            'NoIndex' => 'boolean',
            'TerbitPada' => 'immutable_datetime',
            'TarikPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** Dua syarat yang berbeda: statusnya terbit, dan ada versi terkunci yang tayang. */
    public function tayang(): bool
    {
        return $this->Status->terlihatPublik() && $this->VersiTerbitId !== null;
    }

    /** Hanya yang tayang dan tidak ditandai noindex yang pantas masuk peta situs. */
    public function bolehMasukSitemap(): bool
    {
        return $this->tayang() && ! $this->NoIndex;
    }

    /** @return HasMany<VersiKontenPemasaran, $this> */
    public function versi(): HasMany
    {
        return $this->hasMany(VersiKontenPemasaran::class, 'KontenPemasaranId', 'Id')->orderByDesc('Nomor');
    }

    /** @return BelongsTo<VersiKontenPemasaran, $this> */
    public function versiTerbit(): BelongsTo
    {
        return $this->belongsTo(VersiKontenPemasaran::class, 'VersiTerbitId', 'Id');
    }

    /** @return BelongsTo<VersiKontenPemasaran, $this> */
    public function versiDraf(): BelongsTo
    {
        return $this->belongsTo(VersiKontenPemasaran::class, 'VersiDrafId', 'Id');
    }

    /** @return HasMany<KontenKeywordSeo, $this> */
    public function tautanKeyword(): HasMany
    {
        return $this->hasMany(KontenKeywordSeo::class, 'KontenPemasaranId', 'Id');
    }

    /** @return BelongsTo<Kampanye, $this> */
    public function kampanye(): BelongsTo
    {
        return $this->belongsTo(Kampanye::class, 'KampanyeId', 'Id');
    }
}

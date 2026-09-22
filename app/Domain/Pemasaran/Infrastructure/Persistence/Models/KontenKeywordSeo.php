<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tautan satu konten ke satu keyword, dengan penanda keyword utamanya (MARKETING.md 9). */
final class KontenKeywordSeo extends ModelDasar
{
    protected $table = 'KontenKeywordSeo';

    public $timestamps = false;

    protected $fillable = ['KontenPemasaranId', 'KeywordSeoId', 'Utama', 'DibuatPada'];

    protected function casts(): array
    {
        return [
            'Utama' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<KeywordSeo, $this> */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(KeywordSeo::class, 'KeywordSeoId', 'Id');
    }

    /** @return BelongsTo<KontenPemasaran, $this> */
    public function konten(): BelongsTo
    {
        return $this->belongsTo(KontenPemasaran::class, 'KontenPemasaranId', 'Id');
    }
}
